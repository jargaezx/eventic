<?php
$this->assign('title', __('Permisos'));
$this->assign('subtitle', __('Nuevo'));

$this->Breadcrumbs->add([
    ['title' => __('Permisos'), 'url' => ['controller' => 'Permissions', 'action' => 'index']],
    ['title' => __('Nuevo permiso')],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Seguridad') ?></span>
            <h1><?= __('Nuevo permiso') ?></h1>
            <p><?= __('Agrega una acción del sistema que pueda asignarse a roles.') ?></p>
        </div>
    </div>

    <?= $this->element('permission_form', compact('permission')) ?>
</section>
