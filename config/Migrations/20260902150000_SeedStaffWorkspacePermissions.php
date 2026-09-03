<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class SeedStaffWorkspacePermissions extends AbstractMigration
{
    public function up(): void
    {
        $permissions = [
            ['Staff - Eventos', 'Eventos asignados al staff.', 'Staff', 'Events', 'index'],
            ['Staff - Resumen', 'Resumen operativo del evento.', 'Staff', 'Events', 'view'],
            ['Staff - Escaner', 'Escaneo de pases del evento.', 'Staff', 'Events', 'scan'],
        ];

        foreach ($permissions as [$name, $description, $prefix, $controller, $action]) {
            $this->execute(
                "INSERT INTO permissions (id, name, description, prefix, controller, action, created, modified, active)
                 SELECT UUID(), :name, :description, :prefix, :controller, :action, NOW(), NOW(), 1
                 WHERE NOT EXISTS (
                    SELECT 1 FROM permissions WHERE prefix = :prefix_check AND controller = :controller_check AND action = :action_check
                 )",
                [
                    'name' => $name,
                    'description' => $description,
                    'prefix' => $prefix,
                    'controller' => $controller,
                    'action' => $action,
                    'prefix_check' => $prefix,
                    'controller_check' => $controller,
                    'action_check' => $action,
                ]
            );
        }

        $this->execute(
            "INSERT INTO permissions_roles (id, permission_id, role_id)
             SELECT UUID(), p.id, r.id
             FROM permissions p
             INNER JOIN roles r ON r.name = 'Operador'
             LEFT JOIN permissions_roles pr ON pr.permission_id = p.id AND pr.role_id = r.id
             WHERE p.prefix = 'Staff'
                AND p.controller = 'Events'
                AND p.action IN ('index', 'view', 'scan')
                AND pr.id IS NULL"
        );
    }

    public function down(): void
    {
        $this->execute(
            "DELETE pr FROM permissions_roles pr
             INNER JOIN permissions p ON p.id = pr.permission_id
             WHERE p.prefix = 'Staff'
                AND p.controller = 'Events'
                AND p.action IN ('index', 'view', 'scan')"
        );
        $this->execute(
            "DELETE FROM permissions
             WHERE prefix = 'Staff'
                AND controller = 'Events'
                AND action IN ('index', 'view', 'scan')"
        );
    }
}
