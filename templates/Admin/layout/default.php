<?php
$companyName = env('COMPANY_NAME') ?: env('APP_COMPANY');
$appName = env('APP_NAME') ?: 'EventIC';
$cakeDescription = $companyName ? $companyName . ': ' . $appName : $appName;
$eventicPage = $this->fetch('eventicPage') === '1';
$identity = $this->request->getAttribute('identity');
$currentUser = $identity ? $identity->getOriginalData() : $this->request->getSession()->read('Auth');
$adminNav = [
    ['icon' => 'gauge-high', 'label' => __('Panel'), 'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'dashboard']],
    ['icon' => 'calendar-days', 'label' => __('Eventos'), 'url' => ['prefix' => 'Admin', 'controller' => 'Events', 'action' => 'index']],
    ['icon' => 'users-gear', 'label' => __('Equipo'), 'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index']],
    ['icon' => 'user-shield', 'label' => __('Roles'), 'url' => ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index']],
    ['icon' => 'key', 'label' => __('Permisos'), 'url' => ['prefix' => 'Admin', 'controller' => 'Permissions', 'action' => 'index']],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?= $cakeDescription ?>: <?= $this->fetch('title') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="EventIC, consola profesional para gestion de eventos, accesos y staff.">
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
<body class="eventic-admin-page nova-admin-body">
    <div class="nova-admin-layout">
        <aside class="nova-sidebar" aria-label="<?= __('Navegacion principal') ?>">
            <div class="nova-sidebar-brand">
                <?= $this->Html->link(
                    '<img src="/assets/img/eventic-mark.svg" alt="EventIC">',
                    ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'dashboard'],
                    ['escape' => false]
                ) ?>
            </div>
            <nav class="nova-nav">
                <span class="nova-nav-label"><?= __('Administracion') ?></span>
                <?php foreach ($adminNav as $item): ?>
                    <?= $this->RBAC->link(
                        $this->FontAwesome->icon('fas', $item['icon']) . '<span>' . h($item['label']) . '</span>',
                        $item['url'],
                        ['class' => 'nova-nav-link', 'escape' => false]
                    ) ?>
                <?php endforeach; ?>
                <span class="nova-nav-label"><?= __('Operacion') ?></span>
                <?= $this->RBAC->link(
                    $this->FontAwesome->icon('fas', 'qrcode') . '<span>' . __('Modo staff') . '</span>',
                    ['prefix' => 'Staff', 'controller' => 'Events', 'action' => 'index'],
                    ['class' => 'nova-nav-link', 'escape' => false]
                ) ?>
            </nav>
        </aside>

        <div class="nova-workspace">
            <header class="nova-topbar">
                <div>
                    <span><?= __('EventIC Suite') ?></span>
                    <strong><?= $this->fetch('title') ?: __('Panel') ?></strong>
                </div>
                <div class="nova-topbar-actions">
                    <form class="nova-search" role="search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" placeholder="<?= __('Buscar') ?>">
                    </form>
                    <div class="nova-user-chip">
                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                        <span><?= h($currentUser->email ?? __('Administrador')) ?></span>
                    </div>
                    <?= $this->Html->link(
                        $this->FontAwesome->icon('fas', 'arrow-right-from-bracket'),
                        ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                        ['class' => 'nova-icon-action', 'escape' => false, 'title' => __('Salir')]
                    ) ?>
                </div>
            </header>

            <main class="nova-content">
                <?php if (!$eventicPage): ?>
                    <div class="nova-page-context">
                        <h1><?= $this->fetch('title') ?></h1>
                        <?php
                            echo $this->Breadcrumbs->prepend('Inicio', '/admin/users/dashboard')
                                ->render();
                        ?>
                    </div>
                <?php endif; ?>
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </main>
        </div>
    </div>

    <script src="/assets/js/jquery-3.7.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/eventic-ui.js"></script>
    <script src="/assets/js/eventic-pwa.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
