<?php
$currentUser = $this->request->getAttribute('identity');
?>
<!-- Header -->
<div class="header">

    <!-- Logo -->
    <div class="header-left">
        <a href="admin-dashboard.html" class="logo">
            <img src="/img/logo.png" width="80" height="40" alt="Logo">
        </a>
        <a href="admin-dashboard.html" class="logo2">
            <img src="/img/logo.png" width="80" height="40" alt="Logo">
        </a>
    </div>
    <!-- /Logo -->

    <a id="toggle_btn" href="javascript:void(0);">
        <span class="bar-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </a>

    <!-- Header Title -->
    <div class="page-title-box">
        <h3><?= env('APP_NAME') ?></h3>
    </div>
    <!-- /Header Title -->

    <a id="mobile_btn" class="mobile_btn" href="#sidebar"><i class="fa-solid fa-bars"></i></a>

    <!-- Header Menu -->
    <ul class="nav user-menu">

        <!-- Search -->
        <li class="nav-item">
            <div class="top-nav-search">
                <a href="javascript:void(0);" class="responsive-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </a>
                <form action="search.html">
                    <input class="form-control" type="text" placeholder="Buscar ...">
                    <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
        </li>
        <!-- /Search -->

        <li class="nav-item dropdown has-arrow main-drop">
            <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                <span><?= $currentUser->email ?></span>
            </a>
            <div class="dropdown-menu">
                <?= $this->Html->link(__('Cerrar Sesión'), ['prefix'=>false, 'controller'=>'users', 'action'=>'logout'], ['class'=>'dropdown-item']) ?>
            </div>
        </li>
    </ul>
    <!-- /Header Menu -->

    <!-- Mobile Menu -->
    <div class="dropdown mobile-user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
            <?= $this->Html->link(__('Cerrar Sesión'), ['prefix'=>false, 'controller'=>'users', 'action'=>'logout'], ['class'=>'dropdown-item']) ?>
        </div>
    </div>
    <!-- /Mobile Menu -->

</div>
<!-- /Header -->