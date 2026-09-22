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
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_UNCERTAIN = 'uncertain';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const TYPE_TICKET = 'ticket';
    public const OVERDUE_AFTER_MINUTES = 15;

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
        // Serialize enqueue and manual retry for the same ticket.
        return $this->getConnection()->transactional(function () use ($ticket, $recipientEmail) {
            $this->getConnection()->execute('SELECT id FROM tickets WHERE id = ? FOR UPDATE', [$ticket->id]);

            return $this->enqueueLockedTicket($ticket, $recipientEmail);
        });
    }

    private function enqueueLockedTicket($ticket, ?string $recipientEmail = null)
    {
        $recipientEmail = strtolower(trim((string)($recipientEmail ?: $ticket->email)));

        $existing = $this->find()
            ->where([
                'type' => self::TYPE_TICKET,
                'ticket_id' => $ticket->id,
                'recipient_email' => $recipientEmail,
                'status IN' => [self::STATUS_PENDING, self::STATUS_PREPARING, self::STATUS_PROCESSING, self::STATUS_UNCERTAIN],
            ])
            ->epilog('FOR UPDATE')
            ->first();

        if ($existing) {
            if ($existing->status === self::STATUS_UNCERTAIN) {
                throw new \RuntimeException('La entrega anterior requiere revisión antes de volver a enviar el pase.');
            }
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

    /**
     * Overdue jobs are a subset of pending (including jobs held by a worker).
     * Scheduled backoff starts aging at available_at, not at creation time.
     */
    public function operationalStatus(string $eventId): array
    {
        $cutoff = DateTime::now()->subMinutes(self::OVERDUE_AFTER_MINUTES)->format('Y-m-d H:i:s');
        $row = $this->getConnection()->execute(
            "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status IN ('pending', 'preparing', 'processing') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('preparing', 'processing') THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS processed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = 'uncertain' THEN 1 ELSE 0 END) AS uncertain,
                SUM(CASE
                    WHEN status = 'pending' AND COALESCE(available_at, created) <= ? THEN 1
                    WHEN status IN ('preparing', 'processing') AND COALESCE(locked_at, modified, created) <= ? THEN 1
                    ELSE 0 END) AS overdue
             FROM email_jobs WHERE event_id = ?",
            [$cutoff, $cutoff, $eventId]
        )->fetch('assoc');

        return array_map('intval', $row);
    }

    /**
     * Reuse failed jobs, never create another delivery. Lock tickets in stable
     * order, shared with enqueueTicket(), and recheck status under that lock.
     */
    public function retryFailedForEvent(string $eventId, ?string $jobId = null): array
    {
        return $this->getConnection()->transactional(function () use ($eventId, $jobId): array {
            $jobs = $this->find()
                ->where(['event_id' => $eventId, 'status' => self::STATUS_FAILED])
                ->where($jobId ? ['id' => $jobId] : [])
                ->orderBy(['ticket_id' => 'ASC', 'created' => 'DESC', 'id' => 'ASC'])
                ->all();
            $result = ['retried' => 0, 'skipped' => 0];
            foreach ($jobs as $job) {
                $ticket = $this->getConnection()->execute(
                    'SELECT id, active FROM tickets WHERE id = ? AND event_id = ? FOR UPDATE',
                    [$job->ticket_id, $eventId]
                )->fetch('assoc');
                if (
                    $job->type !== self::TYPE_TICKET || !$ticket || !$ticket['active']
                    || (int)$job->max_attempts < 1 || $job->sent_at !== null
                ) {
                    $result['skipped']++;
                    continue;
                }

                // Locking reads see commits from concurrent retries even on REPEATABLE READ.
                $current = $this->getConnection()->execute(
                    'SELECT status FROM email_jobs WHERE id = ? FOR UPDATE',
                    [$job->id]
                )->fetch('assoc');
                $duplicate = $this->getConnection()->execute(
                    "SELECT id FROM email_jobs
                     WHERE ticket_id = ? AND recipient_email = ? AND type = ? AND id <> ?
                       AND (status IN ('pending', 'preparing', 'processing', 'uncertain')
                            OR (status = 'sent' AND (? IS NULL OR sent_at >= ? OR created >= ?)))
                     LIMIT 1 FOR UPDATE",
                    [$job->ticket_id, $job->recipient_email, self::TYPE_TICKET, $job->id, $job->created, $job->created, $job->created],
                    ['string', 'string', 'string', 'string', 'datetime', 'datetime', 'datetime']
                )->fetch('assoc');
                if (!$current || $current['status'] !== self::STATUS_FAILED || $duplicate) {
                    $result['skipped']++;
                    continue;
                }

                $result['retried'] += $this->updateAll([
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'available_at' => DateTime::now(),
                    'locked_at' => null,
                    'locked_by' => null,
                    'sent_at' => null,
                    'last_error' => null,
                    'modified' => DateTime::now(),
                ], ['id' => $job->id, 'event_id' => $eventId, 'status' => self::STATUS_FAILED]);
            }

            return $result;
        });
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
                'status' => self::STATUS_PREPARING,
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
        $this->updateOwnedJob($job, [
            'status' => self::STATUS_SENT,
            'sent_at' => DateTime::now(),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => null,
        ]);
    }

    public function markFailed($job, \Throwable $exception): void
    {
        $attempts = (int)$job->attempts + 1;
        $failed = $attempts >= (int)$job->max_attempts;
        $delayMinutes = min(60, 2 ** max(0, $attempts - 1));

        $this->updateOwnedJob($job, [
            'status' => $failed ? self::STATUS_FAILED : self::STATUS_PENDING,
            'attempts' => $attempts,
            'available_at' => $failed ? null : DateTime::now()->addMinutes($delayMinutes),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    public function markCancelled($job, string $reason): void
    {
        $this->updateOwnedJob($job, [
            'status' => self::STATUS_CANCELLED,
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => $reason,
        ]);
    }

    private function updateOwnedJob($job, array $fields): void
    {
        $updated = $this->updateAll($fields + ['modified' => DateTime::now()], [
            'id' => $job->id,
            'status IN' => [self::STATUS_PREPARING, self::STATUS_PROCESSING],
            'locked_by' => $job->locked_by,
        ]);
        if ($updated !== 1) {
            throw new \RuntimeException('El trabajo de correo ya no pertenece a este procesador.');
        }
        $job->set($fields);
    }

    public function beginDelivery($job): void
    {
        $this->updateOwnedJob($job, ['status' => self::STATUS_PROCESSING, 'locked_at' => DateTime::now()]);
    }

    public function markUncertain($job, \Throwable $exception): void
    {
        $this->updateOwnedJob($job, [
            'status' => self::STATUS_UNCERTAIN,
            'attempts' => (int)$job->attempts + 1,
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    private function jobLockName(string $id): string
    {
        return 'eventic-mail-' . sha1(($this->getConnection()->config()['database'] ?? '') . ':' . $id);
    }

    public function acquireJobLock(string $id): bool
    {
        return (int)$this->getConnection()->execute('SELECT GET_LOCK(?, 0)', [$this->jobLockName($id)])->fetch('num')[0] === 1;
    }

    public function releaseJobLock(string $id): void
    {
        $this->getConnection()->execute('SELECT RELEASE_LOCK(?)', [$this->jobLockName($id)]);
    }

    /** A live worker owns a connection-scoped lock throughout rendering and SMTP. */
    public function recoverInterrupted(): array
    {
        $cutoff = DateTime::now()->subMinutes(self::OVERDUE_AFTER_MINUTES);
        $jobs = $this->find()->where([
            'status IN' => [self::STATUS_PREPARING, self::STATUS_PROCESSING],
            'OR' => ['locked_at <=' => $cutoff, 'locked_at IS' => null],
        ])->limit(500)->all();
        $result = ['requeued' => 0, 'uncertain' => 0];
        foreach ($jobs as $job) {
            if (!$this->acquireJobLock($job->id)) {
                continue;
            }
            try {
                // Recheck the lease after acquiring the lock; never steal a fresh claim.
                $preparing = $job->status === self::STATUS_PREPARING;
                $updated = $this->updateAll([
                    'status' => $preparing ? self::STATUS_PENDING : self::STATUS_UNCERTAIN,
                    'available_at' => DateTime::now(),
                    'locked_at' => null,
                    'locked_by' => null,
                    'modified' => DateTime::now(),
                    'last_error' => $preparing ? null : 'Procesador interrumpido durante el envío. Verifica la entrega antes de reintentar.',
                ], [
                    'id' => $job->id, 'status' => $job->status,
                    'OR' => ['locked_at <=' => $cutoff, 'locked_at IS' => null],
                ]);
                $result[$preparing ? 'requeued' : 'uncertain'] += $updated;
            } finally {
                $this->releaseJobLock($job->id);
            }
        }

        return $result;
    }

    public function resolveUncertain(string $eventId, string $jobId, string $decision): void
    {
        if (!in_array($decision, ['sent', 'retry'], true)) {
            throw new \InvalidArgumentException('Resolución no válida.');
        }
        $this->getConnection()->transactional(function () use ($eventId, $jobId, $decision): void {
            $job = $this->find()->where(['id' => $jobId, 'event_id' => $eventId, 'status' => self::STATUS_UNCERTAIN])->firstOrFail();
            $this->getConnection()->execute('SELECT id FROM tickets WHERE id = ? FOR UPDATE', [$job->ticket_id]);
            $updated = $this->updateAll([
                'status' => $decision === 'sent' ? self::STATUS_SENT : self::STATUS_FAILED,
                'sent_at' => $decision === 'sent' ? DateTime::now() : null,
                'modified' => DateTime::now(),
                'last_error' => null,
            ], ['id' => $jobId, 'event_id' => $eventId, 'status' => self::STATUS_UNCERTAIN]);
            if ($updated !== 1) {
                throw new \RuntimeException('Este envío ya fue revisado.');
            }
            if ($decision === 'retry' && $this->retryFailedForEvent($eventId, $jobId)['retried'] !== 1) {
                throw new \RuntimeException('El pase no está activo o ya existe otro envío en cola o procesado.');
            }
        });
    }
}
