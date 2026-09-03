<?php
$this->assign('title', __('Roles'));
$this->assign('subtitle', __('Nuevo'));
$this->Breadcrumbs->add([
    ['title' => 'Roles', 'url' => ['controller' => 'Roles', 'action' => 'index']],
    ['title' => 'Nuevo'],
]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Accesos') ?></div>
            <h1 class="eventic-title"><?= __('Nuevo rol') ?></h1>
            <p class="eventic-subtitle"><?= __('Define que puede ver y operar cada perfil dentro del sistema.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Volver', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>
    <?= $this->element('role_form', ['rol' => $rol, 'permissionsByModule' => $permissionsByModule, 'isEdit' => false]) ?>
</div>
