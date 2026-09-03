<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class HardenEventicCore extends AbstractMigration
{
    public function up(): void
    {
        $events = $this->table('events');
        if (!$events->hasColumn('currency')) {
            $events->addColumn('currency', 'string', [
                'default' => 'MXN',
                'limit' => 3,
                'null' => false,
                'after' => 'capacity',
            ]);
        }
        if (!$events->hasColumn('primary_color')) {
            $events->addColumn('primary_color', 'string', [
                'default' => '#2563EB',
                'limit' => 7,
                'null' => false,
                'after' => 'email_message',
            ]);
        }
        if (!$events->hasColumn('accent_color')) {
            $events->addColumn('accent_color', 'string', [
                'default' => '#16A34A',
                'limit' => 7,
                'null' => false,
                'after' => 'primary_color',
            ]);
        }
        if (!$events->hasColumn('email_subject')) {
            $events->addColumn('email_subject', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'after' => 'email_message',
            ]);
        }
        if (!$events->hasColumn('email_footer')) {
            $events->addColumn('email_footer', 'text', [
                'default' => null,
                'null' => true,
                'after' => 'email_subject',
            ]);
        }
        if (!$events->hasIndex(['active', 'event_date'])) {
            $events->addIndex(['active', 'event_date']);
        }
        if (!$events->hasIndex(['deleted'])) {
            $events->addIndex(['deleted']);
        }
        $events->update();

        $ticketConfigurations = $this->table('ticket_configurations');
        if (!$ticketConfigurations->hasColumn('created')) {
            $ticketConfigurations->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'ticket_dir',
            ]);
        }
        if (!$ticketConfigurations->hasColumn('modified')) {
            $ticketConfigurations->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'created',
            ]);
        }
        if (!$ticketConfigurations->hasColumn('active')) {
            $ticketConfigurations->addColumn('active', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'modified',
            ]);
        }
        $ticketConfigurations->update();

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

        $ticketConfigurations = $this->table('ticket_configurations');
        if (!$ticketConfigurations->hasIndex(['event_id'])) {
            $ticketConfigurations
                ->addIndex(['event_id'], ['unique' => true, 'name' => 'ticket_configurations_event_unique'])
                ->update();
        }

        $tickets = $this->table('tickets');
        if (!$tickets->hasColumn('price')) {
            $tickets->addColumn('price', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
                'after' => 'name',
            ]);
        }
        if (!$tickets->hasColumn('currency')) {
            $tickets->addColumn('currency', 'string', [
                'default' => 'MXN',
                'limit' => 3,
                'null' => false,
                'after' => 'price',
            ]);
        }
        if (!$tickets->hasColumn('payment_status')) {
            $tickets->addColumn('payment_status', 'string', [
                'default' => 'free',
                'limit' => 30,
                'null' => false,
                'after' => 'currency',
            ]);
        }
        if (!$tickets->hasColumn('payment_reference')) {
            $tickets->addColumn('payment_reference', 'string', [
                'default' => null,
                'limit' => 191,
                'null' => true,
                'after' => 'payment_status',
            ]);
        }
        if (!$tickets->hasColumn('checked_in_by')) {
            $tickets->addColumn('checked_in_by', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'attended',
            ]);
        }
        if (!$tickets->hasColumn('checked_in_ip')) {
            $tickets->addColumn('checked_in_ip', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => true,
                'after' => 'checked_in_by',
            ]);
        }
        if (!$tickets->hasColumn('checked_in_user_agent')) {
            $tickets->addColumn('checked_in_user_agent', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'after' => 'checked_in_ip',
            ]);
        }
        if (!$tickets->hasIndex(['event_id', 'folio'])) {
            $tickets->addIndex(['event_id', 'folio'], ['unique' => true, 'name' => 'tickets_event_folio_unique']);
        }
        if (!$tickets->hasIndex(['event_id', 'email'])) {
            $tickets->addIndex(['event_id', 'email']);
        }
        if (!$tickets->hasIndex(['event_id', 'active', 'attended'])) {
            $tickets->addIndex(['event_id', 'active', 'attended']);
        }
        if (!$tickets->hasIndex(['checked_in_by'])) {
            $tickets->addIndex(['checked_in_by']);
        }
        $tickets->update();

        $this->execute(
            'ALTER TABLE tickets
             ADD CONSTRAINT fk_tickets_checked_in_by
             FOREIGN KEY (checked_in_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );

        $staffs = $this->table('staffs');
        if (!$staffs->hasColumn('role_label')) {
            $staffs->addColumn('role_label', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => true,
                'after' => 'scan',
            ]);
        }
        $staffs->update();

        $this->execute(
            'DELETE s FROM staffs s
             INNER JOIN staffs keep
                ON keep.event_id = s.event_id
               AND keep.user_id = s.user_id
               AND keep.id <> s.id
             WHERE keep.id = (
                SELECT picked.id FROM (
                    SELECT id
                    FROM staffs
                    WHERE event_id = s.event_id AND user_id = s.user_id
                    ORDER BY active DESC, register DESC, scan DESC, modified DESC, id DESC
                    LIMIT 1
                ) picked
             )'
        );

        $staffs = $this->table('staffs');
        if (!$staffs->hasIndex(['event_id', 'user_id'])) {
            $staffs->addIndex(['event_id', 'user_id'], ['unique' => true, 'name' => 'staffs_event_user_unique']);
        }
        if (!$staffs->hasIndex(['user_id', 'active'])) {
            $staffs->addIndex(['user_id', 'active']);
        }
        $staffs->update();

        $users = $this->table('users');
        if (!$users->hasIndex(['email'])) {
            $users->addIndex(['email'], ['unique' => true, 'name' => 'users_email_unique']);
        }
        if (!$users->hasIndex(['role_id', 'active'])) {
            $users->addIndex(['role_id', 'active']);
        }
        $users->update();

        $permissions = $this->table('permissions');
        if (!$permissions->hasIndex(['prefix', 'controller', 'action'])) {
            $permissions
                ->addIndex(['prefix', 'controller', 'action'], ['unique' => true, 'name' => 'permissions_route_unique'])
                ->update();
        }

        $permissionsRoles = $this->table('permissions_roles');
        if (!$permissionsRoles->hasIndex(['permission_id', 'role_id'])) {
            $permissionsRoles
                ->addIndex(['permission_id', 'role_id'], ['unique' => true, 'name' => 'permissions_roles_unique'])
                ->update();
        }
    }

    public function down(): void
    {
        $this->table('permissions_roles')
            ->removeIndexByName('permissions_roles_unique')
            ->update();

        $this->table('permissions')
            ->removeIndexByName('permissions_route_unique')
            ->update();

        $this->table('users')
            ->removeIndexByName('users_email_unique')
            ->removeIndex(['role_id', 'active'])
            ->update();

        $this->table('staffs')
            ->removeIndexByName('staffs_event_user_unique')
            ->removeIndex(['user_id', 'active'])
            ->removeColumn('role_label')
            ->update();

        $this->table('tickets')
            ->dropForeignKey('checked_in_by', 'fk_tickets_checked_in_by')
            ->removeIndexByName('tickets_event_folio_unique')
            ->removeIndex(['event_id', 'email'])
            ->removeIndex(['event_id', 'active', 'attended'])
            ->removeIndex(['checked_in_by'])
            ->removeColumn('price')
            ->removeColumn('currency')
            ->removeColumn('payment_status')
            ->removeColumn('payment_reference')
            ->removeColumn('checked_in_by')
            ->removeColumn('checked_in_ip')
            ->removeColumn('checked_in_user_agent')
            ->update();

        $this->table('ticket_configurations')
            ->removeIndexByName('ticket_configurations_event_unique')
            ->removeColumn('created')
            ->removeColumn('modified')
            ->removeColumn('active')
            ->update();

        $this->table('events')
            ->removeIndex(['owner_id'])
            ->removeIndex(['active', 'event_date'])
            ->removeIndex(['deleted'])
            ->removeColumn('currency')
            ->removeColumn('primary_color')
            ->removeColumn('accent_color')
            ->removeColumn('email_subject')
            ->removeColumn('email_footer')
            ->update();
    }
}
