<?php
/**
 * @var \App\Model\Entity\Event $event
 * @var \App\Model\Entity\Ticket $ticket
 * @var string|null $coverUrl
 */
$primary = h($event->primary_color ?: '#1e40af');
$accent = h($event->accent_color ?: '#d97706');
$message = trim((string)($event->email_message ?: __('Tu pase digital esta listo. Presenta el codigo QR adjunto al llegar al acceso.')));
$footer = trim((string)($event->email_footer ?: __('Conserva este correo y evita compartir tu pase.')));
$folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
$this->assign('preheader', __('Tu pase para {0} esta listo.', $event->name));
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#f8fafc; margin:0; padding:0;">
    <tr>
        <td align="center" style="padding:32px 14px;">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%; max-width:640px; border-collapse:collapse; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; font-family:Arial, Helvetica, sans-serif;">
                <?php if (!empty($coverUrl)): ?>
                    <tr>
                        <td>
                            <img src="<?= h($coverUrl) ?>" alt="<?= h($event->name) ?>" width="640" style="display:block; width:100%; max-width:640px; height:auto; border:0;">
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td style="background:<?= $primary ?>; padding:30px;">
                        <p style="margin:0 0 8px; color:rgba(255,255,255,0.82); font-size:12px; font-weight:700; letter-spacing:0; text-transform:uppercase;"><?= __('Pase digital confirmado') ?></p>
                        <h1 style="margin:0; color:#ffffff; font-size:30px; line-height:1.18; font-weight:800; letter-spacing:0;"><?= h($event->name) ?></h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:30px;">
                        <p style="margin:0 0 10px; color:#111827; font-size:22px; line-height:1.3; font-weight:800;"><?= __('Hola {0},', h($ticket->name)) ?></p>
                        <div style="margin:0; color:#475569; font-size:16px; line-height:1.6;"><?= nl2br(h($message)) ?></div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:separate; border-spacing:0; margin:26px 0; border:1px solid #e5eaf2; border-radius:8px; overflow:hidden;">
                            <tr>
                                <td style="background:#f8fafc; padding:16px; color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase;"><?= __('Folio') ?></td>
                                <td align="right" style="background:#f8fafc; padding:16px; color:#0f172a; font-size:22px; font-weight:900;"><?= h($folio) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:16px; color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; border-top:1px solid #e5eaf2;"><?= __('Fecha') ?></td>
                                <td align="right" style="padding:16px; color:#0f172a; font-size:15px; font-weight:800; border-top:1px solid #e5eaf2;"><?= h($event->event_date) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:16px; color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; border-top:1px solid #e5eaf2;"><?= __('Acceso') ?></td>
                                <td align="right" style="padding:16px; color:#0f172a; font-size:15px; font-weight:800; border-top:1px solid #e5eaf2;"><?= __('Presenta el QR adjunto') ?></td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px;">
                            <tr>
                                <td style="padding:16px 18px; color:#9a3412; font-size:14px; line-height:1.55; font-weight:700;">
                                    <?= __('El codigo QR es unico. Si lo compartes, otra persona podria usarlo antes que tu.') ?>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:22px 0 0; color:<?= $accent ?>; font-size:15px; line-height:1.5; font-weight:800;"><?= nl2br(h($footer)) ?></p>
                    </td>
                </tr>
            </table>
            <p style="margin:18px 0 0; color:#94a3b8; font-size:12px; line-height:1.5; font-family:Arial, Helvetica, sans-serif;"><?= __('Este correo contiene un pase digital unico para control de acceso.') ?></p>
        </td>
    </tr>
</table>
