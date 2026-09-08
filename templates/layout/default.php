<?php
$companyName = env('APP_COMPANY') ?: env('COMPANY_NAME');
$appName = env('APP_NAME') ?: 'EventIC';
$cakeDescription = $companyName ? $companyName . ': ' . $appName : $appName;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Title -->
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EventIC, plataforma para gestion de eventos, accesos y pases digitales.">
    <?= $this->Html->charset() ?>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#76132c">
    <?= $this->fetch('meta') ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/eventic.css">
    <link rel="stylesheet" href="/assets/css/eventic-nova.css">
    <?= $this->fetch('css') ?>
</head>
<body class="account-page eventic-login-page">
    <div class="main-wrapper eventic-auth-wrapper">
        <div class="account-content">
            <div class="container">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </div>
        </div>
    </div>
    <script src="/assets/js/jquery-3.7.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/eventic-ui.js"></script>
    <script src="/assets/js/eventic-pwa.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
