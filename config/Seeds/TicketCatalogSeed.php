<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class TicketCatalogSeed extends AbstractSeed
{
    public function run(): void
    {
        $this->execute(
            "INSERT INTO ticket_types (id, event_id, name, description, capacity, sort_order, active, created, modified)
             SELECT UUID(), events.id, 'Entrada general', 'Acceso general al evento.', events.capacity, 0, 1, NOW(), NOW()
             FROM events
             LEFT JOIN ticket_types ON ticket_types.event_id = events.id
             WHERE ticket_types.id IS NULL"
        );

        $this->execute(
            "INSERT INTO ticket_rates (id, ticket_type_id, name, description, price, currency, capacity, sort_order, active, created, modified)
             SELECT UUID(), ticket_types.id, 'General', 'Tarifa base del evento.', 0.00, COALESCE(NULLIF(events.currency, ''), 'MXN'), NULL, 0, 1, NOW(), NOW()
             FROM ticket_types
             INNER JOIN events ON events.id = ticket_types.event_id
             LEFT JOIN ticket_rates ON ticket_rates.ticket_type_id = ticket_types.id
             WHERE ticket_rates.id IS NULL"
        );
    }
}
