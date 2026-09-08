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
use App\Service\TicketRenderer;

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
            $eventTable = FactoryLocator::get('Table')->get('Events');
            $eventEntity = $eventTable->get($entity->event_id, contain:['TicketConfigurations']);
            $ticketPath = (new TicketRenderer())->renderTicket($eventEntity, $entity);

            $mailer = new Mailer('default');
            $mailer->setAttachments([$entity->id => $ticketPath])
                ->setEmailFormat('both')
                ->setTo($entity->email)
                ->setSubject($eventEntity->email_subject ?: "{$eventEntity->name}: Boletos")
                ->setViewVars([
                    'event' => $eventEntity,
                    'ticket' => $entity,
                ]);
            $mailer->viewBuilder()->setTemplate('ticket');
            $mailer->deliver();
        }
    }
}
