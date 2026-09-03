<?php
$cakeDescription = env('COMPANY_NAME') . ': ' . env('APP_NAME');
?>
<!DOCTYPE html>
<html data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none">

<head>
    <!-- Title -->
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <!-- META TAGS -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smarthr - Bootstrap Admin Template">
    <meta name="keywords" content="admin, estimates, bootstrap, business, corporate, creative, management, minimal, modern, accounts, invoice, html5, responsive, CRM, Projects">
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">
    <?= $this->Html->charset() ?>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <?= $this->fetch('meta') ?>

    <!-- CSS STYLES -->
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <!-- Lineawesome CSS -->
    <link rel="stylesheet" href="/assets/css/line-awesome.min.css">
    <link rel="stylesheet" href="/assets/css/material.css">
    <!-- Lineawesome CSS -->
    <link rel="stylesheet" href="/assets/css/line-awesome.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="/assets/css/select2.min.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/eventic.css">
    <?= $this->fetch('css') ?>
</head>

<body>
    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <?= $this->element('layout/header') ?>
        <?= $this->element('layout/sidebar') ?>
        <div class="page-wrapper">
            <!-- Page Content -->
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
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
                <!-- /Page Header -->
                <!-- Content Starts -->
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
                <!-- /Content End -->

            </div>
            <!-- /Page Content -->

        </div>
        <!-- /Page Wrapper -->
    </div>
    <!-- /Main Wrapper -->

    <!-- jQuery -->
    <script src="/assets/js/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Core JS -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Slimscroll JS -->
    <script src="/assets/js/jquery.slimscroll.min.js"></script>
    <!-- Select2 -->
    <script src="/assets/js/select2.min.js"></script>
    <!-- Theme Settings JS -->
    <script src="/assets/js/layout.js"></script>
    <script src="/assets/js/theme-settings.js"></script>
    <script src="/assets/js/greedynav.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/eventic-pwa.js"></script>
    <?= $this->fetch('script') ?>
</body>

</html>
