<?php
$this->assign('title', __('Permisos'));
$this->assign('subtitle', __('Detalle'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => __('Permisos'), 'url' => ['controller' => 'Permissions', 'action' => 'index']],
    ['title' => __('Detalle')],
]);
?>
<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Accesos') ?></span>
            <h1 class="eventic-title"><?= h($permission->name) ?></h1>
            <p class="eventic-subtitle"><?= h($permission->description ?: __('Permiso operativo del sistema.')) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'pen') . ' ' . __('Editar'), ['action' => 'edit', $permission->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'arrow-left') . ' ' . __('Volver'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card">
        <div class="eventic-card-heading">
            <div>
                <span class="eventic-eyebrow"><?= __('Permiso') ?></span>
                <h2><?= __('Informacion del permiso') ?></h2>
            </div>
            <span class="eventic-status <?= $permission->active ? 'is-active' : 'is-muted' ?>">
                <?= $permission->active ? __('Activo') : __('Inactivo') ?>
            </span>
        </div>
        <div class="eventic-audit-grid">
            <div><span><?= __('Nombre') ?></span><strong><?= h($permission->name) ?></strong></div>
            <div><span><?= __('Descripcion') ?></span><strong><?= h($permission->description ?: '-') ?></strong></div>
            <div><span><?= __('Creado') ?></span><strong><?= h($permission->created) ?></strong></div>
            <div><span><?= __('Modificado') ?></span><strong><?= h($permission->modified) ?></strong></div>
        </div>
        <div class="eventic-note mt-3">
            <span><?= __('Roles asignados') ?></span>
            <div class="eventic-permission-summary">
                <?php foreach ($permission->roles as $rol): ?>
                    <span><?= h($rol->name) ?></span>
                <?php endforeach; ?>
                <?php if (!$permission->roles): ?>
                    <strong><?= __('Sin roles vinculados') ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
