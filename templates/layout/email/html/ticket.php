<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title><?= h(__('Tu pase digital')) ?></title>
    <style>
        body, table, td { margin:0; font-family:Arial,Helvetica,sans-serif; }
        table { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        @media only screen and (max-width:600px) {
            .ticket-outer { padding:12px 8px !important; }
            .ticket-padding { padding-left:20px !important; padding-right:20px !important; }
            .ticket-title { font-size:25px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; width:100%; background:#f5f7fa; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all;"><?= $this->fetch('preheader') ?></div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f5f7fa" style="width:100%; table-layout:fixed; border-collapse:collapse; background:#f5f7fa;">
        <tr><td class="ticket-outer" align="center" style="padding:28px 12px;">
            <!--[if mso]><table role="presentation" width="680" cellspacing="0" cellpadding="0"><tr><td><![endif]-->
            <div style="width:100%; max-width:680px; margin:0 auto;">
                <?= $this->fetch('content') ?>
                <p style="margin:20px 8px 0; font:12px/1.5 Arial,Helvetica,sans-serif; color:#687385; text-align:center; overflow-wrap:anywhere;"><?= h(env('COMPANY_NAME') ?: 'EventIC') ?></p>
            </div>
            <!--[if mso]></td></tr></table><![endif]-->
        </td></tr>
    </table>
</body>
</html>
