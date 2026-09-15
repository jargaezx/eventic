<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

class EmailJobsTable extends Table
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const TYPE_TICKET = 'ticket';

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('email_jobs');
        $this->setDisplayField('recipient_email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->notEmptyString('type');

        $validator
            ->uuid('event_id')
            ->allowEmptyString('event_id');

        $validator
            ->uuid('ticket_id')
            ->allowEmptyString('ticket_id');

        $validator
            ->email('recipient_email')
            ->notEmptyString('recipient_email');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->allowEmptyString('subject');

        $validator
            ->scalar('payload')
            ->allowEmptyString('payload');

        $validator
            ->scalar('status')
            ->maxLength('status', 30)
            ->notEmptyString('status');

        $validator
            ->nonNegativeInteger('attempts')
            ->allowEmptyString('attempts');

        $validator
            ->nonNegativeInteger('max_attempts')
            ->allowEmptyString('max_attempts');

        return $validator;
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->isNew() && !$entity->id) {
            $entity->id = Text::uuid();
        }
    }

    public function enqueueTicket($ticket, ?string $recipientEmail = null)
    {
        $recipientEmail = strtolower(trim((string)($recipientEmail ?: $ticket->email)));

        $existing = $this->find()
            ->where([
                'type' => self::TYPE_TICKET,
                'ticket_id' => $ticket->id,
                'recipient_email' => $recipientEmail,
                'status IN' => [self::STATUS_PENDING, self::STATUS_PROCESSING],
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        $job = $this->newEntity([
            'type' => self::TYPE_TICKET,
            'event_id' => $ticket->event_id,
            'ticket_id' => $ticket->id,
            'recipient_email' => $recipientEmail,
            'status' => self::STATUS_PENDING,
            'priority' => 0,
            'attempts' => 0,
            'max_attempts' => 3,
            'available_at' => DateTime::now(),
        ]);

        return $this->saveOrFail($job);
    }

    public function claimPending(int $limit, string $workerId): array
    {
        $limit = max(1, min(500, $limit));
        $connection = $this->getConnection();
        $ids = [];

        $connection->transactional(function () use ($connection, $limit, $workerId, &$ids): void {
            $rows = $connection->execute(
                "SELECT id
                 FROM email_jobs
                 WHERE status = ?
                   AND attempts < max_attempts
                   AND (available_at IS NULL OR available_at <= NOW())
                 ORDER BY priority DESC, created ASC
                 LIMIT {$limit}
                 FOR UPDATE",
                [self::STATUS_PENDING]
            )->fetchAll('assoc');

            $ids = array_column($rows, 'id');
            if (!$ids) {
                return;
            }

            $this->updateAll([
                'status' => self::STATUS_PROCESSING,
                'locked_at' => DateTime::now(),
                'locked_by' => $workerId,
                'modified' => DateTime::now(),
            ], ['id IN' => $ids]);
        });

        if (!$ids) {
            return [];
        }

        return $this->find()
            ->where(['EmailJobs.id IN' => $ids])
            ->orderBy(['EmailJobs.priority' => 'DESC', 'EmailJobs.created' => 'ASC'])
            ->all()
            ->toList();
    }

    public function markSent($job): void
    {
        $this->patchEntity($job, [
            'status' => self::STATUS_SENT,
            'sent_at' => DateTime::now(),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => null,
        ]);
        $this->saveOrFail($job);
    }

    public function markFailed($job, \Throwable $exception): void
    {
        $attempts = (int)$job->attempts + 1;
        $failed = $attempts >= (int)$job->max_attempts;
        $delayMinutes = min(60, 2 ** max(0, $attempts - 1));

        $this->patchEntity($job, [
            'status' => $failed ? self::STATUS_FAILED : self::STATUS_PENDING,
            'attempts' => $attempts,
            'available_at' => $failed ? null : DateTime::now()->addMinutes($delayMinutes),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
        $this->saveOrFail($job);
    }

    public function markCancelled($job, string $reason): void
    {
        $this->patchEntity($job, [
            'status' => self::STATUS_CANCELLED,
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => $reason,
        ]);
        $this->saveOrFail($job);
    }
}
