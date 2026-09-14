<?php
declare(strict_types=1);

namespace App\Utility;

class EventDefaults
{
    public const PRIMARY_COLOR = '#76132c';
    public const ACCENT_COLOR = '#c99a3f';
    public const CURRENCY = 'MXN';

    public static function emailSubject($event): string
    {
        $name = trim((string)($event->name ?? ''));

        return $name !== ''
            ? __('Tu pase digital para {0}', $name)
            : __('Tu pase digital EventIC');
    }

    public static function emailMessage($event): string
    {
        $name = trim((string)($event->name ?? 'tu evento'));

        return __(
            'Tu acceso para {0} está listo. Hemos adjuntado tu pase digital con un código QR único para agilizar tu ingreso. Presenta este correo o la imagen del pase al llegar y conserva el QR sin compartirlo, ya que solo podrá validarse una vez.',
            $name
        );
    }

    public static function emailFooter($event): string
    {
        return __('Te recomendamos guardar este correo y llegar con anticipación para que el proceso de acceso sea rápido y ordenado.');
    }
}
