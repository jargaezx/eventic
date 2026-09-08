<?php
$companyName = env('COMPANY_NAME') ?: env('APP_COMPANY');
$appName = env('APP_NAME') ?: 'Eventic';
$cakeDescription = $companyName ? $companyName . ': ' . $appName : $appName;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?= $cakeDescription ?>: <?= $this->fetch('title') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?= $this->Html->charset() ?>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/eventic.css">
    <link rel="stylesheet" href="/assets/css/eventic-nova.css">
    <?= $this->fetch('css') ?>
</head>
<body class="eventic-staff-page">
    <main class="eventic-staff-shell">
        <header class="eventic-staff-header">
            <div class="eventic-staff-brand">
                <img src="/assets/img/eventic-mark.svg" alt="<?= h(env('APP_NAME') ?: 'EventIC') ?>">
                <span><?= h(env('APP_NAME') ?: 'EventIC') ?></span>
                <strong><?= $this->fetch('title') ?></strong>
            </div>
            <?= $this->Html->link(__('Salir'), ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </header>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
    <script src="/assets/js/jquery-3.7.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/eventic-ui.js"></script>
    <script src="/assets/js/eventic-pwa.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
