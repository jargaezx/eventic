<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class ExtendEventTeamRolesAndAudit extends AbstractMigration
{
    public function up(): void
    {
        $events = $this->table('events');
        if (!$events->hasColumn('created_by')) {
            $events->addColumn('created_by', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'owner_id',
            ]);
        }
        if (!$events->hasColumn('modified_by')) {
            $events->addColumn('modified_by', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'created_by',
            ]);
        }
        if (!$events->hasIndex(['created_by'])) {
            $events->addIndex(['created_by']);
        }
        if (!$events->hasIndex(['modified_by'])) {
            $events->addIndex(['modified_by']);
        }
        $events->update();

        $tickets = $this->table('tickets');
        if (!$tickets->hasColumn('registered_by')) {
            $tickets->addColumn('registered_by', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'user_id',
            ]);
        }
        if (!$tickets->hasIndex(['registered_by'])) {
            $tickets->addIndex(['registered_by']);
        }
        if (!$tickets->hasIndex(['event_id', 'registered_by', 'active'])) {
            $tickets->addIndex(['event_id', 'registered_by', 'active'], [
                'name' => 'tickets_event_registered_active',
            ]);
        }
        $tickets->update();

        $staffs = $this->table('staffs');
        if (!$staffs->hasColumn('role')) {
            $staffs->addColumn('role', 'string', [
                'default' => 'access',
                'limit' => 40,
                'null' => false,
                'after' => 'user_id',
            ]);
        }
        if (!$staffs->hasColumn('can_manage_event')) {
            $staffs->addColumn('can_manage_event', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'role_label',
            ]);
        }
        if (!$staffs->hasColumn('can_manage_staff')) {
            $staffs->addColumn('can_manage_staff', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'can_manage_event',
            ]);
        }
        if (!$staffs->hasColumn('can_register')) {
            $staffs->addColumn('can_register', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'can_manage_staff',
            ]);
        }
        if (!$staffs->hasColumn('can_scan')) {
            $staffs->addColumn('can_scan', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'can_register',
            ]);
        }
        if (!$staffs->hasColumn('can_view_reports')) {
            $staffs->addColumn('can_view_reports', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'can_scan',
            ]);
        }
        if (!$staffs->hasColumn('sales_limit')) {
            $staffs->addColumn('sales_limit', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'after' => 'can_view_reports',
            ]);
        }
        if (!$staffs->hasColumn('sales_count')) {
            $staffs->addColumn('sales_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'after' => 'sales_limit',
            ]);
        }
        if (!$staffs->hasIndex(['event_id', 'role', 'active'])) {
            $staffs->addIndex(['event_id', 'role', 'active'], [
                'name' => 'staffs_event_role_active',
            ]);
        }
        $staffs->update();

        $this->execute(
            "UPDATE staffs
             SET role = CASE
                    WHEN register = 1 AND scan = 1 THEN 'manager'
                    WHEN register = 1 THEN 'seller'
                    WHEN scan = 1 THEN 'access'
                    ELSE 'supervisor'
                 END,
                 can_manage_event = 0,
                 can_manage_staff = 0,
                 can_register = COALESCE(register, 0),
                 can_scan = COALESCE(scan, 0),
                 can_view_reports = CASE WHEN register = 1 AND scan = 1 THEN 1 ELSE 0 END,
                 active = COALESCE(active, 1)
             WHERE role IS NULL OR role = 'access'"
        );

        $this->execute(
            'UPDATE tickets
             SET registered_by = user_id
             WHERE registered_by IS NULL AND user_id IS NOT NULL'
        );

        $this->execute(
            'UPDATE events
             SET created_by = owner_id
             WHERE created_by IS NULL AND owner_id IS NOT NULL'
        );

        $this->execute(
            'UPDATE events
             SET modified_by = owner_id
             WHERE modified_by IS NULL AND owner_id IS NOT NULL'
        );

        $this->execute(
            'ALTER TABLE events
             ADD CONSTRAINT fk_events_created_by
             FOREIGN KEY (created_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );

        $this->execute(
            'ALTER TABLE events
             ADD CONSTRAINT fk_events_modified_by
             FOREIGN KEY (modified_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );

        $this->execute(
            'ALTER TABLE tickets
             ADD CONSTRAINT fk_tickets_registered_by
             FOREIGN KEY (registered_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        $this->table('tickets')
            ->dropForeignKey('registered_by', 'fk_tickets_registered_by')
            ->removeIndexByName('tickets_event_registered_active')
            ->removeIndex(['registered_by'])
            ->removeColumn('registered_by')
            ->update();

        $this->table('events')
            ->dropForeignKey('created_by', 'fk_events_created_by')
            ->dropForeignKey('modified_by', 'fk_events_modified_by')
            ->removeIndex(['created_by'])
            ->removeIndex(['modified_by'])
            ->removeColumn('created_by')
            ->removeColumn('modified_by')
            ->update();

        $this->table('staffs')
            ->removeIndexByName('staffs_event_role_active')
            ->removeColumn('role')
            ->removeColumn('can_manage_event')
            ->removeColumn('can_manage_staff')
            ->removeColumn('can_register')
            ->removeColumn('can_scan')
            ->removeColumn('can_view_reports')
            ->removeColumn('sales_limit')
            ->removeColumn('sales_count')
            ->update();
    }
}
