<?php
$this->assign('title', __('Permisos'));
$this->assign('subtitle', __('Editar'));

$this->Breadcrumbs->add([
    ['title' => __('Permisos'), 'url' => ['controller' => 'Permissions', 'action' => 'index']],
    ['title' => __('Editar permiso')],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Seguridad') ?></span>
            <h1><?= h($permission->name) ?></h1>
            <p><?= __('Actualiza la ruta y disponibilidad de este permiso.') ?></p>
        </div>
    </div>

    <?= $this->element('permission_form', compact('permission')) ?>
</section>
