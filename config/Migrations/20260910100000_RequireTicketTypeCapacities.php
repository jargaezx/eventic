<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RequireTicketTypeCapacities extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'UPDATE ticket_types tt
             INNER JOIN (
                SELECT event_id, COUNT(*) AS active_types
                FROM ticket_types
                WHERE active = 1
                GROUP BY event_id
             ) counts ON counts.event_id = tt.event_id
             INNER JOIN events e ON e.id = tt.event_id
             SET tt.capacity = CASE
                WHEN counts.active_types = 1 AND tt.active = 1 THEN e.capacity
                WHEN tt.capacity IS NULL THEN 0
                ELSE tt.capacity
             END
             WHERE tt.capacity IS NULL'
        );

        $ticketTypes = $this->table('ticket_types');
        if ($ticketTypes->hasColumn('capacity')) {
            $ticketTypes->changeColumn('capacity', 'integer', [
                'default' => 0,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])->update();
        }
    }

    public function down(): void
    {
        $ticketTypes = $this->table('ticket_types');
        if ($ticketTypes->hasColumn('capacity')) {
            $ticketTypes->changeColumn('capacity', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])->update();
        }
    }
}
