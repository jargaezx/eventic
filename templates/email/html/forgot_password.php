<?php
$resetUrl = $this->Url->build(['controller' => 'Users', 'action' => 'resetPassword', $token], ['fullBase' => true]);
$this->assign('preheader', __('Restablece tu acceso a EventIC.'));
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:#f5f7fa;">
    <tr>
        <td align="center" style="padding:34px 14px;">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%; max-width:640px; border-collapse:separate; border-spacing:0; font-family:'Plus Jakarta Sans', Inter, Arial, Helvetica, sans-serif;">
                <tr>
                    <td style="background:#76132c; border-radius:12px 12px 0 0; padding:30px 32px;">
                        <p style="margin:0 0 12px; color:#f3d99d; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;">EventIC</p>
                        <h1 style="margin:0; color:#ffffff; font-size:32px; line-height:1.12; font-weight:800; letter-spacing:-0.02em;"><?= __('Recupera tu acceso') ?></h1>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef; padding:32px;">
                        <p style="margin:0 0 16px; color:#17202a; font-size:18px; line-height:1.5; font-weight:800;"><?= __('Solicitaste restablecer tu contrasena.') ?></p>
                        <p style="margin:0 0 24px; color:#687385; font-size:15px; line-height:1.7;"><?= __('Usa el siguiente enlace para definir una nueva contrasena y continuar operando tus eventos en EventIC.') ?></p>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:separate;">
                            <tr>
                                <td style="background:#76132c; border-radius:8px;">
                                    <a href="<?= h($resetUrl) ?>" target="_blank" style="display:inline-block; color:#ffffff; font-size:15px; font-weight:800; padding:14px 22px; text-decoration:none;"><?= __('Restablecer contrasena') ?></a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:24px 0 0; color:#687385; font-size:13px; line-height:1.6;"><?= __('Si no solicitaste este cambio, puedes ignorar este correo.') ?></p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#4d0d1f; border-radius:0 0 12px 12px; padding:18px 32px;">
                        <p style="margin:0; color:#f3d99d; font-size:12px; line-height:1.6;"><?= __('Este enlace es personal y debe usarse solo por el propietario de la cuenta.') ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
