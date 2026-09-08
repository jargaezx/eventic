<?php
/**
 * @var \App\View\AppView $this
 */
$this->disableAutoLayout();
$appName = env('APP_NAME') ?: 'EventIC';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="EventIC, plataforma profesional para gestion de eventos, venta de pases y validacion QR.">
    <title><?= h($appName) ?></title>
    <?= $this->Html->meta('icon') ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/eventic-nova.css">
</head>
<body class="eventic-login-page">
    <main class="eventic-home-shell">
        <section class="eventic-home-hero">
            <div class="eventic-login-brand">
                <img src="/assets/img/eventic-mark.svg" alt="<?= h($appName) ?>">
            </div>
            <div>
                <span class="eventic-login-kicker"><?= __('Plataforma profesional para eventos') ?></span>
                <h1><?= __('Gestiona registros, pases y accesos con precision.') ?></h1>
                <p><?= __('EventIC centraliza la administracion del evento, la emision de boletos digitales y la validacion QR para equipos de operacion en sitio.') ?></p>
            </div>
            <div class="eventic-home-actions">
                <?= $this->Html->link(
                    $this->FontAwesome->icon('fas', 'user-shield') . ' ' . __('Entrar a Admin'),
                    '/admin/login',
                    ['class' => 'btn btn-primary', 'escape' => false]
                ) ?>
                <?= $this->Html->link(
                    $this->FontAwesome->icon('fas', 'qrcode') . ' ' . __('Entrar a Staff'),
                    '/staff/login',
                    ['class' => 'btn btn-outline-primary', 'escape' => false]
                ) ?>
            </div>
        </section>
    </main>
</body>
</html>
