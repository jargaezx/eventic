<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class TicketCatalogSeed extends AbstractSeed
{
    public function run(): void
    {
        $this->execute(
            "INSERT INTO ticket_types (id, event_id, name, description, price, currency, capacity, sort_order, active, created, modified)
             SELECT UUID(), events.id, 'Entrada general', 'Acceso general al evento.', 0.00, COALESCE(NULLIF(events.currency, ''), 'MXN'), events.capacity, 0, 1, NOW(), NOW()
             FROM events
             LEFT JOIN ticket_types ON ticket_types.event_id = events.id
             WHERE ticket_types.id IS NULL"
        );
    }
}
