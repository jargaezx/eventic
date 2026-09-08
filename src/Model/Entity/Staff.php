<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Staff extends Entity
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_SELLER = 'seller';
    public const ROLE_ACCESS = 'access';
    public const ROLE_SUPERVISOR = 'supervisor';

    protected array $_accessible = [
        '*' => true
    ];

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => __('Administrador del evento'),
            self::ROLE_MANAGER => __('Gerente / supervisor'),
            self::ROLE_SELLER => __('Registro / punto de venta'),
            self::ROLE_ACCESS => __('Accesos / escaneo'),
            self::ROLE_SUPERVISOR => __('Solo consulta'),
        ];
    }

    public static function roleDescriptions(): array
    {
        return [
            self::ROLE_ADMIN => __('Configura el evento, equipo, registros, accesos y reportes.'),
            self::ROLE_MANAGER => __('Supervisa estado operativo, asistencia y reportes.'),
            self::ROLE_SELLER => __('Registra o vende pases desde el mostrador asignado.'),
            self::ROLE_ACCESS => __('Valida pases QR y consulta datos operativos de acceso.'),
            self::ROLE_SUPERVISOR => __('Consulta el estado del evento sin modificar operacion.'),
        ];
    }

    public static function roleDefaults(?string $role): array
    {
        return match ($role) {
            self::ROLE_ADMIN => [
                'can_manage_event' => true,
                'can_manage_staff' => true,
                'can_register' => true,
                'can_scan' => true,
                'can_view_reports' => true,
            ],
            self::ROLE_MANAGER => [
                'can_manage_event' => false,
                'can_manage_staff' => false,
                'can_register' => true,
                'can_scan' => true,
                'can_view_reports' => true,
            ],
            self::ROLE_SELLER => [
                'can_manage_event' => false,
                'can_manage_staff' => false,
                'can_register' => true,
                'can_scan' => false,
                'can_view_reports' => false,
            ],
            self::ROLE_ACCESS => [
                'can_manage_event' => false,
                'can_manage_staff' => false,
                'can_register' => false,
                'can_scan' => true,
                'can_view_reports' => false,
            ],
            default => [
                'can_manage_event' => false,
                'can_manage_staff' => false,
                'can_register' => false,
                'can_scan' => false,
                'can_view_reports' => true,
            ],
        };
    }

    public static function normalizeRole(?string $role): string
    {
        return array_key_exists((string)$role, self::roleOptions()) ? (string)$role : self::ROLE_ACCESS;
    }
}
