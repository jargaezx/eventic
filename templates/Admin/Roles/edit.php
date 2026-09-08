<?php
$this->assign('title', __('Roles'));
$this->assign('subtitle', __('Editar'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Roles', 'url' => ['controller' => 'Roles', 'action' => 'index']],
    ['title' => 'Editar'],
]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Accesos') ?></div>
            <h1 class="eventic-title"><?= h($rol->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Ajusta los permisos disponibles para este perfil.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Volver', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>
    <?= $this->element('role_form', ['rol' => $rol, 'permissionsByModule' => $permissionsByModule, 'isEdit' => true]) ?>
</div>
