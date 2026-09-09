<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class SimplifyTicketCatalogToTypes extends AbstractMigration
{
    public function up(): void
    {
        $ticketTypes = $this->table('ticket_types');
        if (!$ticketTypes->hasColumn('price')) {
            $ticketTypes->addColumn('price', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'description',
            ]);
        }
        if (!$ticketTypes->hasColumn('currency')) {
            $ticketTypes->addColumn('currency', 'string', [
                'limit' => 3,
                'null' => false,
                'default' => 'MXN',
                'after' => 'price',
            ]);
        }
        if (!$ticketTypes->hasIndex(['event_id', 'active', 'price'])) {
            $ticketTypes->addIndex(['event_id', 'active', 'price'], [
                'name' => 'ticket_types_event_active_price',
            ]);
        }
        $ticketTypes->update();

        $this->execute(
            "UPDATE ticket_types tt
             INNER JOIN events e ON e.id = tt.event_id
             LEFT JOIN ticket_rates tr ON tr.id = (
                SELECT picked.id
                FROM (
                    SELECT id, ticket_type_id
                    FROM ticket_rates
                    WHERE active = 1
                    ORDER BY sort_order ASC, name ASC
                ) picked
                WHERE picked.ticket_type_id = tt.id
                LIMIT 1
             )
             SET tt.price = COALESCE(tr.price, tt.price, 0.00),
                 tt.currency = COALESCE(NULLIF(tr.currency, ''), NULLIF(e.currency, ''), 'MXN')"
        );

        if (!$this->hasTable('staff_ticket_type_limits')) {
            $this->table('staff_ticket_type_limits', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('staff_id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('event_id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('ticket_type_id', 'char', ['limit' => 36, 'null' => false])
                ->addColumn('sales_limit', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('sales_count', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['staff_id', 'ticket_type_id'], [
                    'name' => 'staff_type_limit_unique',
                    'unique' => true,
                ])
                ->addIndex(['event_id', 'ticket_type_id', 'active'], [
                    'name' => 'staff_type_limits_event_type',
                ])
                ->create();

            $this->execute(
                'ALTER TABLE staff_ticket_type_limits
                 CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci'
            );
            $this->execute(
                'ALTER TABLE staff_ticket_type_limits
                 ADD CONSTRAINT fk_staff_type_limits_staff
                 FOREIGN KEY (staff_id) REFERENCES staffs(id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
            $this->execute(
                'ALTER TABLE staff_ticket_type_limits
                 ADD CONSTRAINT fk_staff_type_limits_event
                 FOREIGN KEY (event_id) REFERENCES events(id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
            $this->execute(
                'ALTER TABLE staff_ticket_type_limits
                 ADD CONSTRAINT fk_staff_type_limits_ticket_type
                 FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
        }

        $this->execute(
            'UPDATE staff_ticket_type_limits sttl
             INNER JOIN (
                SELECT s.id AS staff_id, t.ticket_type_id, COUNT(*) AS total
                FROM staffs s
                INNER JOIN tickets t ON t.event_id = s.event_id
                    AND t.registered_by = s.user_id
                    AND t.active = 1
                    AND t.ticket_type_id IS NOT NULL
                GROUP BY s.id, t.ticket_type_id
             ) totals ON totals.staff_id = sttl.staff_id
                AND totals.ticket_type_id = sttl.ticket_type_id
             SET sttl.sales_count = totals.total'
        );
    }

    public function down(): void
    {
        if ($this->hasTable('staff_ticket_type_limits')) {
            $this->table('staff_ticket_type_limits')
                ->dropForeignKey('staff_id', 'fk_staff_type_limits_staff')
                ->dropForeignKey('event_id', 'fk_staff_type_limits_event')
                ->dropForeignKey('ticket_type_id', 'fk_staff_type_limits_ticket_type')
                ->drop()
                ->save();
        }

        $ticketTypes = $this->table('ticket_types');
        if ($ticketTypes->hasIndex(['event_id', 'active', 'price'])) {
            $ticketTypes->removeIndexByName('ticket_types_event_active_price');
        }
        if ($ticketTypes->hasColumn('currency')) {
            $ticketTypes->removeColumn('currency');
        }
        if ($ticketTypes->hasColumn('price')) {
            $ticketTypes->removeColumn('price');
        }
        $ticketTypes->update();
    }
}
