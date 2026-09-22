<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Mailer\Mailer;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\Routing\Router;
use Cake\Utility\Text;
use App\Service\TicketRenderer;
use App\Utility\EventDefaults;

class TicketsTable extends Table
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tickets');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('CounterCache', [
            'Events' => [
                'ticket_count'   => [
                    'conditions' => ['Tickets.active' => TRUE],
                ],
                'ticket_attended_count'   => [
                    'conditions' => ['Tickets.attended IS NOT' => NULL, 'Tickets.active' => TRUE],
                ],
            ],
        ]);

        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
        $this->belongsTo('TicketTypes', [
            'foreignKey' => 'ticket_type_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('TicketRates', [
            'foreignKey' => 'ticket_rate_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('RegisteredByUsers', [
            'className' => 'Users',
            'foreignKey' => 'registered_by',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('CheckedInUsers', [
            'className' => 'Users',
            'foreignKey' => 'checked_in_by',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('CancelledByUsers', [
            'className' => 'Users',
            'foreignKey' => 'cancelled_by',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('event_id')
            ->allowEmptyString('event_id');

        $validator
            ->uuid('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->uuid('ticket_type_id')
            ->allowEmptyString('ticket_type_id');

        $validator
            ->uuid('ticket_rate_id')
            ->allowEmptyString('ticket_rate_id');

        $validator
            ->scalar('ticket_type_name')
            ->maxLength('ticket_type_name', 120)
            ->allowEmptyString('ticket_type_name');

        $validator
            ->scalar('ticket_rate_name')
            ->maxLength('ticket_rate_name', 120)
            ->allowEmptyString('ticket_rate_name');

        $validator
            ->uuid('registered_by')
            ->allowEmptyString('registered_by');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name');


        $validator
            ->dateTime('attended')
            ->allowEmptyDateTime('attended');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        $validator
            ->dateTime('last_emailed')
            ->allowEmptyDateTime('last_emailed');

        $validator
            ->nonNegativeInteger('email_attempt_count')
            ->allowEmptyString('email_attempt_count');

        $validator
            ->dateTime('cancelled')
            ->allowEmptyDateTime('cancelled');

        $validator
            ->uuid('cancelled_by')
            ->allowEmptyString('cancelled_by');

        $validator
            ->scalar('cancelled_reason')
            ->allowEmptyString('cancelled_reason');

        $validator
            ->decimal('price')
            ->greaterThanOrEqual('price', 0)
            ->allowEmptyString('price');

        $validator
            ->scalar('currency')
            ->maxLength('currency', 3)
            ->allowEmptyString('currency');

        $validator
            ->scalar('payment_status')
            ->maxLength('payment_status', 30)
            ->allowEmptyString('payment_status');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['event_id'], 'Events'), ['errorField' => 'event_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if (!$entity->isNew() || $entity->folio || !$entity->event_id) {
            return;
        }

        $lastFolio = $this->find()
            ->select(['max_folio' => $this->find()->func()->max('folio')])
            ->where(['event_id' => $entity->event_id])
            ->enableHydration(false)
            ->first();

        $entity->folio = ((int)($lastFolio['max_folio'] ?? 0)) + 1;
    }

    public function afterSave($event, $entity, $options)
    {
        if ($entity->isNew()) {
            $this->queueTicketEmail($entity);
        }
    }

    public function queueTicketEmail($ticket, ?string $recipientEmail = null)
    {
        $emailJobs = FactoryLocator::get('Table')->get('EmailJobs');

        return $emailJobs->enqueueTicket($ticket, $recipientEmail);
    }

    public function checkIn(string $ticketId, string $eventId, string $userId, string $ip, string $userAgent): bool
    {
        return $this->getConnection()->transactional(function () use ($ticketId, $eventId, $userId, $ip, $userAgent): bool {
            $affected = $this->updateAll([
                'attended' => DateTime::now(),
                'checked_in_by' => $userId,
                'checked_in_ip' => $ip,
                'checked_in_user_agent' => mb_substr($userAgent, 0, 255),
            ], ['id' => $ticketId, 'event_id' => $eventId, 'active' => true, 'attended IS' => null]);
            if ($affected !== 1) {
                return false;
            }
            $this->getConnection()->execute(
                'UPDATE events SET ticket_attended_count = COALESCE(ticket_attended_count, 0) + 1 WHERE id = ?',
                [$eventId]
            );

            return true;
        });
    }

    public function deliverTicketEmail($ticket, ?string $recipientEmail = null, ?callable $beforeSend = null): void
    {
        $eventTable = FactoryLocator::get('Table')->get('Events');
        $eventEntity = $eventTable->get($ticket->event_id, contain:['TicketConfigurations']);
        $ticketPath = (new TicketRenderer())->renderTicket($eventEntity, $ticket);
        $recipientEmail = strtolower(trim((string)($recipientEmail ?: $ticket->email)));
        $attachments = [$ticket->id . '.png' => $ticketPath];
        $attachments += $this->ticketQrAttachment((string)$ticket->id);
        $coverAttachment = $this->eventCoverAttachment($eventEntity);
        if ($coverAttachment) {
            $attachments += $coverAttachment;
        }

        $mailer = new Mailer('default');
        $mailer->setAttachments($attachments)
            ->setEmailFormat('both')
            ->setTo($recipientEmail)
            ->setSubject($eventEntity->email_subject ?: EventDefaults::emailSubject($eventEntity))
            ->setViewVars([
                'event' => $eventEntity,
                'ticket' => $ticket,
                'coverUrl' => $this->eventCoverUrl($eventEntity),
                'coverCid' => $coverAttachment ? 'event-cover' : null,
                'qrCid' => 'ticket-qr',
            ]);
        $mailer->viewBuilder()->setTemplate('ticket')->setLayout('ticket');
        if ($beforeSend) {
            $beforeSend();
        }
        $mailer->deliver();

        $this->getConnection()->execute(
            'UPDATE tickets
             SET last_emailed = ?, email_attempt_count = email_attempt_count + 1
             WHERE id = ?',
            [DateTime::now()->format('Y-m-d H:i:s'), $ticket->id]
        );
    }

    public function deliverTestTicketEmail($event, string $email): void
    {
        $ticketType = null;
        foreach ($event->ticket_types ?? [] as $type) {
            if ($type->active) {
                $ticketType = $type;
                break;
            }
        }

        $testId = 'test-' . Text::uuid();
        $ticket = $this->newEntity([
            'id' => $testId,
            'event_id' => $event->id,
            'name' => __('Asistente de prueba'),
            'email' => $email,
            'folio' => 0,
            'ticket_type_id' => $ticketType->id ?? null,
            'ticket_type_name' => $ticketType->name ?? __('Entrada digital'),
            'price' => $ticketType ? (float)$ticketType->price : 0,
            'currency' => $ticketType->currency ?? ($event->currency ?: 'MXN'),
            'payment_status' => $ticketType && (float)$ticketType->price > 0 ? 'paid' : 'free',
            'active' => true,
            'is_test' => true,
        ]);
        $ticket->setNew(false);

        $filename = 'eventic-pase-prueba-' . Text::slug(strtolower((string)$event->name)) . '-' . $testId . '.png';
        $ticketPath = TMP . $filename;

        try {
            (new TicketRenderer())->renderTicketToPath($event, $ticket, $ticketPath);
            $attachments = [
                $filename => [
                    'file' => $ticketPath,
                    'mimetype' => 'image/png',
                ],
            ];
            $coverAttachment = $this->eventCoverAttachment($event);
            $attachments += $this->ticketQrAttachment((string)$ticket->id);
            if ($coverAttachment) {
                $attachments += $coverAttachment;
            }

            $mailer = new Mailer('default');
            $mailer
                ->setAttachments($attachments)
                ->setEmailFormat('both')
                ->setTo($email)
                ->setSubject(__('Prueba - {0}', $event->email_subject ?: EventDefaults::emailSubject($event)))
                ->setViewVars([
                    'event' => $event,
                    'ticket' => $ticket,
                    'coverUrl' => $this->eventCoverUrl($event),
                    'coverCid' => $coverAttachment ? 'event-cover' : null,
                    'qrCid' => 'ticket-qr',
                ]);
            $mailer->viewBuilder()->setTemplate('ticket')->setLayout('ticket');
            $mailer->deliver();
        } finally {
            if (is_file($ticketPath)) {
                @unlink($ticketPath);
            }
        }
    }

    private function eventCoverUrl($event): ?string
    {
        if (!$event->cover || !$event->cover_dir) {
            return null;
        }

        $dir = preg_replace('#^webroot/#', '', str_replace('\\', '/', (string)$event->cover_dir));
        $candidate = ROOT . DS . $event->cover_dir . 'card-' . $event->cover;
        $filename = is_file($candidate) ? 'card-' . $event->cover : $event->cover;

        return Router::url('/' . $dir . $filename, true);
    }

    private function eventCoverAttachment($event): ?array
    {
        if (!$event->cover || !$event->cover_dir) {
            return null;
        }

        $basePath = ROOT . DS . $event->cover_dir;
        $candidate = $basePath . 'card-' . $event->cover;
        $file = is_file($candidate) ? $candidate : $basePath . $event->cover;
        if (!is_file($file)) {
            return null;
        }

        try {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($file)->scaleDown(1200, 900)->blendTransparency('ffffff');
            return ['event-cover.jpg' => [
                'data' => (string)$image->toJpeg(85),
                'mimetype' => 'image/jpeg',
                'contentId' => 'event-cover',
                'contentDisposition' => false,
            ]];
        } catch (\Throwable $exception) {
            \Cake\Log\Log::warning('No se pudo preparar la portada del correo: ' . $exception->getMessage());
            return null;
        }
    }

    private function ticketQrAttachment(string $id): array
    {
        return ['codigo-qr.png' => [
            'data' => (new TicketRenderer())->renderQr($id),
            'mimetype' => 'image/png',
            'contentId' => 'ticket-qr',
            'contentDisposition' => false,
        ]];
    }
}
