<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\EmailJobsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Utility\Text;

class ProcessEmailQueueCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Procesa correos pendientes de EventIC.')
            ->addOption('limit', [
                'help' => 'Cantidad máxima de correos a procesar.',
                'default' => '50',
            ])
            ->addOption('worker', [
                'help' => 'Identificador opcional del trabajador.',
                'default' => null,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $limit = max(1, min(500, (int)$args->getOption('limit')));
        $workerId = mb_substr((string)($args->getOption('worker') ?: gethostname()), 0, 70) . '-' . Text::uuid();

        /** @var \App\Model\Table\EmailJobsTable $emailJobs */
        $emailJobs = $this->fetchTable('EmailJobs');
        /** @var \App\Model\Table\TicketsTable $tickets */
        $tickets = $this->fetchTable('Tickets');

        $processed = 0;
        $recovered = $emailJobs->recoverInterrupted();
        $hasErrors = $recovered['uncertain'] > 0;
        for ($index = 0; $index < $limit; $index++) {
            $job = $emailJobs->claimPending(1, $workerId)[0] ?? null;
            if (!$job) {
                break;
            }
            if (!$emailJobs->acquireJobLock($job->id)) {
                continue;
            }
            try {
                $job = $emailJobs->get($job->id);
                if ($job->status !== EmailJobsTable::STATUS_PREPARING || $job->locked_by !== $workerId) {
                    continue;
                }
                if ($job->type !== EmailJobsTable::TYPE_TICKET || !$job->ticket_id) {
                    $emailJobs->markCancelled($job, 'Tipo de correo no soportado.');
                    continue;
                }

                $ticket = $tickets->find()
                    ->where(['Tickets.id' => $job->ticket_id])
                    ->first();

                if (!$ticket) {
                    $emailJobs->markCancelled($job, 'El pase ya no existe.');
                    continue;
                }
                if (!$ticket->active) {
                    $emailJobs->markCancelled($job, 'El pase fue cancelado antes del envío.');
                    continue;
                }

                $tickets->deliverTicketEmail($ticket, (string)$job->recipient_email, function () use ($emailJobs, $job): void {
                    $emailJobs->beginDelivery($job);
                });
                $emailJobs->markSent($job);
                $processed++;
            } catch (\Throwable $exception) {
                $hasErrors = true;
                if ($job->status === EmailJobsTable::STATUS_PROCESSING) {
                    $emailJobs->markUncertain($job, $exception);
                } else {
                    $emailJobs->markFailed($job, $exception);
                }
                $this->log($exception->getMessage(), 'error');
            } finally {
                $emailJobs->releaseJobLock($job->id);
            }
        }

        $io->out(__('{0} correos procesados.', $processed));
        if ($recovered['requeued'] || $recovered['uncertain']) {
            $io->out(__('{0} trabajos recuperados; {1} entregas requieren revisión.', $recovered['requeued'], $recovered['uncertain']));
        }

        return $hasErrors ? static::CODE_ERROR : static::CODE_SUCCESS;
    }
}
