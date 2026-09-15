<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateEmailJobs extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('email_jobs')) {
            return;
        }

        $this->table('email_jobs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('event_id', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('ticket_id', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('recipient_email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('subject', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('payload', 'text', ['null' => true, 'default' => null])
            ->addColumn('status', 'string', ['limit' => 30, 'null' => false, 'default' => 'pending'])
            ->addColumn('priority', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('attempts', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('max_attempts', 'integer', ['null' => false, 'default' => 3, 'signed' => false])
            ->addColumn('available_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('locked_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('locked_by', 'string', ['limit' => 120, 'null' => true, 'default' => null])
            ->addColumn('sent_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('last_error', 'text', ['null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['status', 'available_at', 'priority'], ['name' => 'email_jobs_ready'])
            ->addIndex(['ticket_id', 'status'], ['name' => 'email_jobs_ticket_status'])
            ->addIndex(['event_id', 'status'], ['name' => 'email_jobs_event_status'])
            ->addIndex(['recipient_email'])
            ->create();

        $this->execute(
            'ALTER TABLE email_jobs
             CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci'
        );

        $this->execute(
            'ALTER TABLE email_jobs
             ADD CONSTRAINT fk_email_jobs_event
             FOREIGN KEY (event_id) REFERENCES events(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->execute(
            'ALTER TABLE email_jobs
             ADD CONSTRAINT fk_email_jobs_ticket
             FOREIGN KEY (ticket_id) REFERENCES tickets(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('email_jobs')) {
            return;
        }

        $this->table('email_jobs')
            ->dropForeignKey('event_id', 'fk_email_jobs_event')
            ->dropForeignKey('ticket_id', 'fk_email_jobs_ticket')
            ->drop()
            ->save();
    }
}
