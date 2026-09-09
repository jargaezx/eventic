<?php
$this->assign('preheader', __('Notificacion de EventIC.'));
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#f5f7fa;">
    <tr>
        <td align="center" style="padding:34px 14px;">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%; max-width:640px; border-collapse:separate; border-spacing:0; font-family:'Plus Jakarta Sans', Inter, Arial, Helvetica, sans-serif;">
                <tr>
                    <td style="background:#76132c; border-radius:12px 12px 0 0; padding:28px 32px;">
                        <p style="margin:0 0 12px; color:#f3d99d; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;">EventIC</p>
                        <h1 style="margin:0; color:#ffffff; font-size:30px; line-height:1.12; font-weight:800; letter-spacing:-0.02em;"><?= __('Notificacion del sistema') ?></h1>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef; padding:32px;">
                        <div style="color:#687385; font-size:15px; line-height:1.7;">
                            <?= nl2br(h($content ?? '')) ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#4d0d1f; border-radius:0 0 12px 12px; padding:18px 32px;">
                        <p style="margin:0; color:#f3d99d; font-size:12px; line-height:1.6;"><?= __('Mensaje enviado desde EventIC.') ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
