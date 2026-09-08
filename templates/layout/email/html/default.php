<!doctype html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= $this->fetch('title') ?></title>
    <style media="all" type="text/css">
        @media only screen and (max-width: 640px) {

            .main p,
            .main td,
            .main span {
                font-size: 16px !important;
            }

            .wrapper {
                padding: 8px !important;
            }

            .content {
                padding: 0 !important;
            }

            .container {
                padding: 0 !important;
                padding-top: 8px !important;
                width: 100% !important;
            }

            .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            .btn table {
                max-width: 100% !important;
                width: 100% !important;
            }

            .btn a {
                font-size: 16px !important;
                max-width: 100% !important;
                width: 100% !important;
            }
        }

        @media all {
            .ExternalClass {
                width: 100%;
            }

            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }

            .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
            }

            #MessageViewBody a {
                color: inherit;
                text-decoration: none;
                font-size: inherit;
                font-family: inherit;
                font-weight: inherit;
                line-height: inherit;
            }
        }
    </style>
</head>

<body style="font-family: 'Plus Jakarta Sans', Inter, Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; font-size: 16px; line-height: 1.45; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; background-color: #f8fafc; margin: 0; padding: 0;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f8fafc; width: 100%;" width="100%" bgcolor="#f8fafc">
        <tr>
            <td style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; vertical-align: top;" valign="top">&nbsp;</td>
            <td class="container" style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; vertical-align: top; max-width: 680px; padding: 0; padding-top: 26px; width: 680px; margin: 0 auto;" width="680" valign="top">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 680px; padding: 0;">

                    <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;"><?= $this->fetch('preheader') ?></span>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: transparent; border: 0; width: 100%;" width="100%">

                        <tr>
                            <td class="wrapper" style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; vertical-align: top; box-sizing: border-box; padding: 0;" valign="top">
                                <?= $this->fetch('content') ?>
                            </td>
                        </tr>

                    </table>

                    <div class="footer" style="clear: both; padding: 20px 0 28px; text-align: center; width: 100%;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                            <tr>
                                <td class="content-block" style="font-family: Arial, Helvetica, sans-serif; vertical-align: top; color: #94a3b8; font-size: 12px; text-align: center;" valign="top" align="center">
                                    <span class="apple-link" style="color: #94a3b8; font-size: 12px; text-align: center;"><?= h(env('COMPANY_NAME') ?: env('APP_NAME')) ?></span>
                                    <br><a href="<?= h(env('COMPANY_WEB')) ?>" style="text-decoration: none; color: #64748b; font-size: 12px; text-align: center;"><?= h(env('COMPANY_WEB')) ?></a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; vertical-align: top;" valign="top">&nbsp;</td>
        </tr>
    </table>
</body>

</html>
