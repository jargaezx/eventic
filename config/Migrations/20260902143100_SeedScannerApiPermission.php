<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class SeedScannerApiPermission extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "INSERT INTO permissions (id, name, description, prefix, controller, action, created, modified, active)
             SELECT UUID(), 'Validar boletos por API', 'Permite marcar entradas como utilizadas desde el escaner.', 'Api', 'Tickets', 'attend', NOW(), NOW(), 1
             WHERE NOT EXISTS (
                SELECT 1 FROM permissions WHERE prefix = 'Api' AND controller = 'Tickets' AND action = 'attend'
             )"
        );

        $this->execute(
            "INSERT INTO permissions_roles (id, permission_id, role_id)
             SELECT UUID(), p.id, r.id
             FROM permissions p
             INNER JOIN roles r ON r.active = 1
             LEFT JOIN permissions_roles pr ON pr.permission_id = p.id AND pr.role_id = r.id
             WHERE p.prefix = 'Api'
               AND p.controller = 'Tickets'
               AND p.action = 'attend'
               AND pr.id IS NULL"
        );
    }

    public function down(): void
    {
        $permission = $this->fetchRow(
            "SELECT id FROM permissions WHERE prefix = 'Api' AND controller = 'Tickets' AND action = 'attend' LIMIT 1"
        );

        if (!$permission) {
            return;
        }

        $this->execute('DELETE FROM permissions_roles WHERE permission_id = :id', ['id' => $permission['id']]);
        $this->execute('DELETE FROM permissions WHERE id = :id', ['id' => $permission['id']]);
    }
}
