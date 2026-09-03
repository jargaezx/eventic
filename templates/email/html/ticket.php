<?php
/**
 * @var \App\Model\Entity\Event $event
 * @var \App\Model\Entity\Ticket $ticket
 */
$primary = h($event->primary_color ?: '#1c63f2');
$accent = h($event->accent_color ?: '#0ea5a4');
$message = trim((string)($event->email_message ?: __('Tu pase digital esta listo. Presenta el codigo QR adjunto al llegar al acceso.')));
$footer = trim((string)($event->email_footer ?: __('Conserva este correo y evita compartir tu pase.')));
$this->assign('preheader', __('Tu pase para {0} esta listo.', $event->name));
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
    <tr>
        <td style="background: <?= $primary ?>; border-radius: 8px 8px 0 0; padding: 28px;">
            <p style="margin: 0 0 8px; color: rgba(255,255,255,0.72); font-size: 13px; font-weight: 700; text-transform: uppercase;"><?= __('Pase digital') ?></p>
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; line-height: 1.15;"><?= h($event->name) ?></h1>
        </td>
    </tr>
    <tr>
        <td style="padding: 28px;">
            <p style="margin: 0 0 18px; color: #344054; font-size: 18px;"><?= __('Hola {0},', h($ticket->name)) ?></p>
            <div style="color: #475467; font-size: 16px; line-height: 1.6;"><?= nl2br(h($message)) ?></div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border-collapse: collapse; border: 1px solid #e4e7ec; border-radius: 8px;">
                <tr>
                    <td style="padding: 14px; color: #667085; font-size: 13px; text-transform: uppercase;"><?= __('Folio') ?></td>
                    <td style="padding: 14px; color: #111827; font-weight: 700; text-align: right;"><?= h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?></td>
                </tr>
                <tr>
                    <td style="padding: 14px; color: #667085; font-size: 13px; text-transform: uppercase; border-top: 1px solid #e4e7ec;"><?= __('Fecha') ?></td>
                    <td style="padding: 14px; color: #111827; font-weight: 700; text-align: right; border-top: 1px solid #e4e7ec;"><?= h($event->event_date) ?></td>
                </tr>
                <tr>
                    <td style="padding: 14px; color: #667085; font-size: 13px; text-transform: uppercase; border-top: 1px solid #e4e7ec;"><?= __('Acceso') ?></td>
                    <td style="padding: 14px; color: #111827; font-weight: 700; text-align: right; border-top: 1px solid #e4e7ec;"><?= __('Validacion con QR') ?></td>
                </tr>
            </table>
            <p style="margin: 0; color: <?= $accent ?>; font-size: 15px; font-weight: 700;"><?= nl2br(h($footer)) ?></p>
        </td>
    </tr>
</table>
