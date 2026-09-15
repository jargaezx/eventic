<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\EmailJobsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

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
        $workerId = (string)($args->getOption('worker') ?: gethostname() . '-' . getmypid());

        /** @var \App\Model\Table\EmailJobsTable $emailJobs */
        $emailJobs = $this->fetchTable('EmailJobs');
        /** @var \App\Model\Table\TicketsTable $tickets */
        $tickets = $this->fetchTable('Tickets');

        $processed = 0;
        foreach ($emailJobs->claimPending($limit, $workerId) as $job) {
            try {
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

                $tickets->deliverTicketEmail($ticket, (string)$job->recipient_email);
                $emailJobs->markSent($job);
                $processed++;
            } catch (\Throwable $exception) {
                $emailJobs->markFailed($job, $exception);
                $this->log($exception->getMessage(), 'error');
            }
        }

        $io->out(__('{0} correos procesados.', $processed));

        return static::CODE_SUCCESS;
    }
}
