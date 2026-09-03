<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class AclSeed extends AbstractSeed
{
    public function run(): void
    {
        $this->execute(
            "INSERT INTO roles (id, name, description, created, modified, active)
             SELECT UUID(), 'Operador', 'Personal operativo para apoyar registro y escaneo de eventos.', NOW(), NOW(), 1
             WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Operador')"
        );

        $permissions = [
            ['Eventos - Ver', 'Ver el detalle de eventos asignados.', 'Admin', 'Events', 'view'],
            ['Eventos - Registrar', 'Registrar asistentes al evento.', 'Admin', 'Events', 'register'],
            ['Eventos - Checkout', 'Capturar asistentes manualmente o por archivo.', 'Admin', 'Events', 'checkout'],
            ['Eventos - Escanear', 'Abrir el escaner del evento.', 'Admin', 'Events', 'scan'],
            ['Validar boletos por API', 'Permite marcar entradas como utilizadas desde el escaner.', 'Api', 'Tickets', 'attend'],
            ['Dashboard', 'Acceder al tablero operativo.', 'Admin', 'Users', 'dashboard'],
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
             WHERE p.active = 1
               AND pr.id IS NULL
               AND (
                   (p.prefix = 'Admin' AND p.controller IN ('Events', 'Users'))
                   OR (p.prefix = 'Api' AND p.controller = 'Tickets' AND p.action = 'attend')
                   OR (p.prefix = 'Staff' AND p.controller = 'Events')
               )"
        );
    }
}
