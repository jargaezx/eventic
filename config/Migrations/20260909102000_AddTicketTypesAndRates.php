<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddTicketTypesAndRates extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('ticket_types')) {
            $this->table('ticket_types', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('event_id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('description', 'text', ['null' => true, 'default' => null])
                ->addColumn('capacity', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['event_id'])
                ->addIndex(['event_id', 'active'], ['name' => 'ticket_types_event_active'])
                ->addIndex(['event_id', 'sort_order'], ['name' => 'ticket_types_event_sort'])
                ->create();

            $this->execute(
                'ALTER TABLE ticket_types
                 ADD CONSTRAINT fk_ticket_types_event
                 FOREIGN KEY (event_id) REFERENCES events(id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
        }

        if (!$this->hasTable('ticket_rates')) {
            $this->table('ticket_rates', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('ticket_type_id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('description', 'text', ['null' => true, 'default' => null])
                ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'default' => '0.00'])
                ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'default' => 'MXN'])
                ->addColumn('capacity', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['ticket_type_id'])
                ->addIndex(['ticket_type_id', 'active'], ['name' => 'ticket_rates_type_active'])
                ->addIndex(['ticket_type_id', 'sort_order'], ['name' => 'ticket_rates_type_sort'])
                ->create();

            $this->execute(
                'ALTER TABLE ticket_rates
                 ADD CONSTRAINT fk_ticket_rates_ticket_type
                 FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
        }

        $tickets = $this->table('tickets');
        if (!$tickets->hasColumn('ticket_type_id')) {
            $tickets->addColumn('ticket_type_id', 'char', [
                'limit' => 36,
                'null' => true,
                'default' => null,
                'after' => 'event_id',
            ]);
        }
        if (!$tickets->hasColumn('ticket_rate_id')) {
            $tickets->addColumn('ticket_rate_id', 'char', [
                'limit' => 36,
                'null' => true,
                'default' => null,
                'after' => 'ticket_type_id',
            ]);
        }
        if (!$tickets->hasColumn('ticket_type_name')) {
            $tickets->addColumn('ticket_type_name', 'string', [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'ticket_rate_id',
            ]);
        }
        if (!$tickets->hasColumn('ticket_rate_name')) {
            $tickets->addColumn('ticket_rate_name', 'string', [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'ticket_type_name',
            ]);
        }
        if (!$tickets->hasIndex(['event_id', 'ticket_type_id', 'active'])) {
            $tickets->addIndex(['event_id', 'ticket_type_id', 'active'], [
                'name' => 'tickets_event_type_active',
            ]);
        }
        if (!$tickets->hasIndex(['ticket_rate_id'])) {
            $tickets->addIndex(['ticket_rate_id']);
        }
        $tickets->update();

        $this->execute(
            'ALTER TABLE tickets
             ADD CONSTRAINT fk_tickets_ticket_type
             FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->execute(
            'ALTER TABLE tickets
             ADD CONSTRAINT fk_tickets_ticket_rate
             FOREIGN KEY (ticket_rate_id) REFERENCES ticket_rates(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );

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

    public function down(): void
    {
        $tickets = $this->table('tickets');
        if ($tickets->hasColumn('ticket_rate_id')) {
            $tickets->dropForeignKey('ticket_rate_id', 'fk_tickets_ticket_rate');
        }
        if ($tickets->hasColumn('ticket_type_id')) {
            $tickets->dropForeignKey('ticket_type_id', 'fk_tickets_ticket_type');
        }
        if ($tickets->hasIndex(['event_id', 'ticket_type_id', 'active'])) {
            $tickets->removeIndexByName('tickets_event_type_active');
        }
        if ($tickets->hasIndex(['ticket_rate_id'])) {
            $tickets->removeIndex(['ticket_rate_id']);
        }
        foreach (['ticket_rate_name', 'ticket_type_name', 'ticket_rate_id', 'ticket_type_id'] as $column) {
            if ($tickets->hasColumn($column)) {
                $tickets->removeColumn($column);
            }
        }
        $tickets->update();

        if ($this->hasTable('ticket_rates')) {
            $this->table('ticket_rates')
                ->dropForeignKey('ticket_type_id', 'fk_ticket_rates_ticket_type')
                ->drop()
                ->save();
        }
        if ($this->hasTable('ticket_types')) {
            $this->table('ticket_types')
                ->dropForeignKey('event_id', 'fk_ticket_types_event')
                ->drop()
                ->save();
        }
    }
}
