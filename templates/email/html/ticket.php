<?php
$message = trim((string)($event->email_message ?: \App\Utility\EventDefaults::emailMessage($event)));
$footer = trim((string)($event->email_footer ?: \App\Utility\EventDefaults::emailFooter($event)));
$isTest = (bool)$ticket->get('is_test');
$folio = $isTest ? __('PRUEBA') : str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
$eventDate = $event->event_date ? $event->event_date->format('d/m/Y, H:i') : __('Por confirmar');
$this->assign('preheader', h(__('Tu pase para {0} está listo.', $event->name)));
$details = [
    __('Fecha y hora') => $eventDate,
    __('Ubicación') => $event->location ?: __('Por confirmar'),
    __('Tipo de boleto') => $ticket->ticket_type_name ?: __('Entrada general'),
    __('Importe') => $this->Number->currency((float)$ticket->price, $ticket->currency ?: ($event->currency ?: 'MXN')),
];
?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; table-layout:fixed; border-collapse:collapse; background:#ffffff; color:#17202a;">
    <tr>
        <td class="ticket-padding" bgcolor="#76132c" style="padding:30px 32px; background:#76132c; color:#ffffff; border-radius:12px 12px 0 0;">
            <p style="margin:0 0 18px; font:700 13px Arial,Helvetica,sans-serif; color:#f3d99d; letter-spacing:2px;">EVENTIC · <?= $isTest ? __('VISTA DE PRUEBA') : __('PASE CONFIRMADO') ?></p>
            <h1 class="ticket-title" style="margin:0; font:700 30px/1.25 Arial,Helvetica,sans-serif; color:#ffffff; overflow-wrap:anywhere; word-wrap:break-word;"><?= h($event->name) ?></h1>
        </td>
    </tr>
    <?php if (!empty($coverCid)): ?>
    <tr>
        <td style="padding:0; background:#ffffff;">
            <img src="cid:<?= h($coverCid) ?>" alt="<?= h(__('Portada de {0}', $event->name)) ?>" width="680" style="display:block; width:100%; max-width:680px; height:auto; border:0; color:#17202a; font:16px Arial,Helvetica,sans-serif;">
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="ticket-padding" style="padding:30px 32px 22px;">
            <h2 style="margin:0 0 12px; font:700 22px/1.35 Arial,Helvetica,sans-serif; overflow-wrap:anywhere; word-wrap:break-word;"><?= h(__('Hola, {0}', $ticket->name)) ?></h2>
            <p style="margin:0; font:16px/1.65 Arial,Helvetica,sans-serif; color:#465264; overflow-wrap:anywhere; word-wrap:break-word;"><?= nl2br(h($message)) ?></p>
        </td>
    </tr>
    <tr>
        <td class="ticket-padding" align="center" style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; table-layout:fixed; border-collapse:separate; background:#f5f7fa; border:1px solid #e5e9ef; border-radius:12px;">
                <tr><td align="center" style="padding:24px 12px 8px;">
                    <p style="margin:0 0 6px; font:700 12px Arial,Helvetica,sans-serif; color:#687385; letter-spacing:1px;"><?= __('FOLIO') ?></p>
                    <p style="margin:0; font:700 30px/1.2 Arial,Helvetica,sans-serif; color:#76132c; overflow-wrap:anywhere;"><?= h($folio) ?></p>
                </td></tr>
                <?php if (!empty($qrCid)): ?>
                <tr><td align="center" style="padding:8px 0;">
                    <img src="cid:<?= h($qrCid) ?>" alt="<?= h(__('Código QR del pase {0}. También está disponible en el boleto adjunto.', $folio)) ?>" width="240" height="240" style="display:block; width:240px; max-width:100%; height:auto; border:0; background:#ffffff; font:14px/1.4 Arial,Helvetica,sans-serif; color:#17202a;">
                </td></tr>
                <?php endif; ?>
                <tr><td align="center" style="padding:8px 16px 24px;">
                    <p style="margin:0; font:700 16px/1.5 Arial,Helvetica,sans-serif; color:#17202a;"><?= $isTest ? __('Prueba de diseño · No permite acceso') : __('Presenta este QR al llegar') ?></p>
                    <p style="margin:8px 0 0; font:14px/1.5 Arial,Helvetica,sans-serif; color:#465264;"><?= __('Tu boleto completo está adjunto como imagen PNG. Guárdalo en tu teléfono antes del evento.') ?></p>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="ticket-padding" style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; table-layout:fixed; border-collapse:collapse;">
                <?php foreach ($details as $label => $value): ?>
                <tr><td style="padding:12px 0; border-bottom:1px solid #e5e9ef;">
                    <p style="margin:0 0 5px; font:700 12px/1.4 Arial,Helvetica,sans-serif; color:#687385;"><?= h($label) ?></p>
                    <p style="margin:0; font:600 16px/1.5 Arial,Helvetica,sans-serif; color:#17202a; overflow-wrap:anywhere; word-wrap:break-word;"><?= h($value) ?></p>
                </td></tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <tr><td class="ticket-padding" style="padding:0 32px 28px;">
        <p style="margin:0 0 12px; font:14px/1.6 Arial,Helvetica,sans-serif; color:#465264;"><?= $isTest ? __('Este correo permite revisar el diseño y los archivos adjuntos antes de enviar pases reales.') : __('El QR es personal y permite un solo acceso. Evita compartirlo.') ?></p>
        <p style="margin:0; font:14px/1.6 Arial,Helvetica,sans-serif; color:#465264; overflow-wrap:anywhere; word-wrap:break-word;"><?= nl2br(h($footer)) ?></p>
    </td></tr>
    <tr><td class="ticket-padding" bgcolor="#4d0d1f" style="padding:20px 32px; background:#4d0d1f; border-radius:0 0 12px 12px;">
        <p style="margin:0; font:13px/1.6 Arial,Helvetica,sans-serif; color:#f3d99d;"><?= __('¿No ves las imágenes? Abre el boleto PNG adjunto: contiene los datos y el QR de tu pase.') ?></p>
    </td></tr>
</table>
