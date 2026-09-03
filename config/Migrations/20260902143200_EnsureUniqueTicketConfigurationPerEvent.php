<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class EnsureUniqueTicketConfigurationPerEvent extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'DELETE tc FROM ticket_configurations tc
             INNER JOIN ticket_configurations keep
                ON keep.event_id = tc.event_id
               AND keep.id <> tc.id
             WHERE keep.id = (
                SELECT picked.id FROM (
                    SELECT id
                    FROM ticket_configurations
                    WHERE event_id = tc.event_id
                    ORDER BY qr_size DESC, x DESC, y DESC, id DESC
                    LIMIT 1
                ) picked
             )'
        );

        $this->execute(
            'ALTER TABLE ticket_configurations
             ADD UNIQUE INDEX ticket_configurations_event_unique (event_id)'
        );
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE ticket_configurations DROP INDEX ticket_configurations_event_unique');
    }
}
