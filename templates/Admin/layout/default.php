<?php
$cakeDescription = env('COMPANY_NAME') . ': ' . env('APP_NAME');
$eventicPage = $this->fetch('eventicPage') === '1';
?>
<!DOCTYPE html>
<html lang="es" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none">

<head>
    <!-- Title -->
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Eventic, consola profesional para gestion de eventos, accesos y staff.">
    <?= $this->Html->charset() ?>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <?= $this->fetch('meta') ?>

    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/line-awesome.min.css">
    <link rel="stylesheet" href="/assets/css/material.css">
    <link rel="stylesheet" href="/assets/css/select2.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/eventic.css">
    <?= $this->fetch('css') ?>
</head>

<body class="eventic-admin-page">
    <div class="main-wrapper eventic-admin-wrapper">
        <?= $this->element('layout/header') ?>
        <?= $this->element('layout/sidebar') ?>
        <div class="page-wrapper">
            <div class="content container-fluid">
                <?php if (!$eventicPage): ?>
                    <div class="page-header eventic-legacy-page-header">
                        <div class="row">
                            <div class="col-sm-12">
                                <h3 class="page-title"><?= $this->fetch('title') ?> : <?= $this->fetch('subtitle') ?></h3>
                                <?php
                                    echo $this->Breadcrumbs->prepend('Inicio', '/admin/users/dashboard')
                                    ->render();
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </div>

        </div>
    </div>

    <script src="/assets/js/jquery-3.7.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/jquery.slimscroll.min.js"></script>
    <script src="/assets/js/select2.min.js"></script>
    <script src="/assets/js/layout.js"></script>
    <script src="/assets/js/theme-settings.js"></script>
    <script src="/assets/js/greedynav.js"></script>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/eventic-ui.js"></script>
    <script src="/assets/js/eventic-pwa.js"></script>
    <?= $this->fetch('script') ?>
</body>

</html>
