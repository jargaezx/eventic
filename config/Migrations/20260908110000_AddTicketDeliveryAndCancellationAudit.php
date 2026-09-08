<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddTicketDeliveryAndCancellationAudit extends AbstractMigration
{
    public function up(): void
    {
        $tickets = $this->table('tickets');

        if (!$tickets->hasColumn('last_emailed')) {
            $tickets->addColumn('last_emailed', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'payment_reference',
            ]);
        }
        if (!$tickets->hasColumn('email_attempt_count')) {
            $tickets->addColumn('email_attempt_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'after' => 'last_emailed',
            ]);
        }
        if (!$tickets->hasColumn('cancelled')) {
            $tickets->addColumn('cancelled', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'checked_in_by',
            ]);
        }
        if (!$tickets->hasColumn('cancelled_by')) {
            $tickets->addColumn('cancelled_by', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'cancelled',
            ]);
        }
        if (!$tickets->hasColumn('cancelled_reason')) {
            $tickets->addColumn('cancelled_reason', 'text', [
                'default' => null,
                'null' => true,
                'after' => 'cancelled_by',
            ]);
        }
        if (!$tickets->hasIndex(['cancelled_by'])) {
            $tickets->addIndex(['cancelled_by']);
        }
        if (!$tickets->hasIndex(['event_id', 'active', 'cancelled'])) {
            $tickets->addIndex(['event_id', 'active', 'cancelled'], [
                'name' => 'tickets_event_active_cancelled',
            ]);
        }
        $tickets->update();

        $this->execute(
            'UPDATE tickets
             SET cancelled = modified
             WHERE active = 0 AND cancelled IS NULL'
        );

        $this->execute(
            'ALTER TABLE tickets
             ADD CONSTRAINT fk_tickets_cancelled_by
             FOREIGN KEY (cancelled_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        $this->table('tickets')
            ->dropForeignKey('cancelled_by', 'fk_tickets_cancelled_by')
            ->removeIndexByName('tickets_event_active_cancelled')
            ->removeIndex(['cancelled_by'])
            ->removeColumn('last_emailed')
            ->removeColumn('email_attempt_count')
            ->removeColumn('cancelled')
            ->removeColumn('cancelled_by')
            ->removeColumn('cancelled_reason')
            ->update();
    }
}
