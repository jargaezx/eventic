<?php
$this->assign('title', __('Roles'));
$this->assign('subtitle', __('Detalle'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => __('Roles'), 'url' => ['controller' => 'Roles', 'action' => 'index']],
    ['title' => __('Detalle')],
]);
?>
<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Accesos') ?></span>
            <h1 class="eventic-title"><?= h($rol->name) ?></h1>
            <p class="eventic-subtitle"><?= h($rol->description ?: __('Perfil de permisos del sistema.')) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'pen') . ' ' . __('Editar'), ['action' => 'edit', $rol->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'arrow-left') . ' ' . __('Volver'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card">
        <div class="eventic-card-heading">
            <div>
                <span class="eventic-eyebrow"><?= __('Rol') ?></span>
                <h2><?= __('Informacion del rol') ?></h2>
            </div>
            <span class="eventic-status <?= $rol->active ? 'is-active' : 'is-muted' ?>">
                <?= $rol->active ? __('Activo') : __('Inactivo') ?>
            </span>
        </div>
        <div class="eventic-audit-grid">
            <div><span><?= __('Nombre') ?></span><strong><?= h($rol->name) ?></strong></div>
            <div><span><?= __('Descripcion') ?></span><strong><?= h($rol->description ?: '-') ?></strong></div>
            <div><span><?= __('Creado') ?></span><strong><?= h($rol->created) ?></strong></div>
            <div><span><?= __('Modificado') ?></span><strong><?= h($rol->modified) ?></strong></div>
            <div><span><?= __('Permisos') ?></span><strong><?= count($rol->permissions ?? []) ?></strong></div>
        </div>
    </div>
</section>
