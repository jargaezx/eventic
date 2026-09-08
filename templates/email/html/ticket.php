<?php
/**
 * @var \App\Model\Entity\Event $event
 * @var \App\Model\Entity\Ticket $ticket
 * @var string|null $coverUrl
 */
$message = trim((string)($event->email_message ?: __('Tu pase digital esta listo. Presenta el codigo QR adjunto al llegar al acceso.')));
$footer = trim((string)($event->email_footer ?: __('Conserva este correo y evita compartir tu pase.')));
$folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
$this->assign('preheader', __('Tu pase para {0} esta listo.', $event->name));
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#f5f7fa; margin:0; padding:0;">
    <tr>
        <td align="center" style="padding:34px 14px;">
            <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="width:100%; max-width:680px; border-collapse:separate; border-spacing:0; font-family:Inter, Arial, Helvetica, sans-serif;">
                <tr>
                    <td style="background:#76132c; border-radius:10px 10px 0 0; padding:30px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 14px; color:#f3d99d; font-size:13px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">EventIC</p>
                                    <h1 style="margin:0; color:#ffffff; font-family:Inter, Arial, Helvetica, sans-serif; font-size:34px; line-height:1.08; font-weight:800; letter-spacing:-0.02em;"><?= h($event->name) ?></h1>
                                </td>
                                <td align="right" style="vertical-align:top;">
                                    <span style="display:inline-block; background:#fbf5e8; border-radius:999px; color:#76132c; font-size:12px; font-weight:800; padding:10px 14px;"><?= __('Pase confirmado') ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?php if (!empty($coverUrl)): ?>
                    <tr>
                        <td style="background:#ffffff;">
                            <img src="<?= h($coverUrl) ?>" alt="<?= h($event->name) ?>" width="680" style="display:block; width:100%; max-width:680px; height:auto; border:0;">
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td style="background:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef; padding:34px 32px 8px;">
                        <p style="margin:0 0 10px; color:#17202a; font-size:24px; line-height:1.25; font-weight:800;"><?= __('Hola {0},', h($ticket->name)) ?></p>
                        <div style="margin:0; color:#687385; font-size:16px; line-height:1.7;"><?= nl2br(h($message)) ?></div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef; padding:24px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="background:#f5f7fa; border:1px solid #e5e9ef; border-radius:10px; padding:20px;">
                                    <p style="margin:0 0 8px; color:#76132c; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;"><?= __('Folio') ?></p>
                                    <p style="margin:0; color:#17202a; font-size:34px; line-height:1; font-weight:900;"><?= h($folio) ?></p>
                                </td>
                                <td width="12" style="font-size:0; line-height:0;">&nbsp;</td>
                                <td style="background:#f5f7fa; border:1px solid #e5e9ef; border-radius:10px; padding:20px;">
                                    <p style="margin:0 0 8px; color:#76132c; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;"><?= __('Fecha') ?></p>
                                    <p style="margin:0; color:#17202a; font-size:16px; line-height:1.35; font-weight:800;"><?= h($event->event_date) ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef; padding:0 32px 34px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#fbf5e8; border:1px solid #efe0bd; border-radius:10px;">
                            <tr>
                                <td style="padding:18px 20px; color:#4d0d1f; font-size:14px; line-height:1.6; font-weight:700;">
                                    <?= __('El codigo QR es unico. Si lo compartes, otra persona podria usarlo antes que tu.') ?>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:22px 0 0; color:#76132c; font-size:15px; line-height:1.6; font-weight:800;"><?= nl2br(h($footer)) ?></p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#4d0d1f; border-radius:0 0 10px 10px; padding:20px 32px;">
                        <p style="margin:0; color:#f3d99d; font-size:12px; line-height:1.6;"><?= __('Este correo contiene un pase digital unico para control de acceso. El QR viene adjunto como imagen.') ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
