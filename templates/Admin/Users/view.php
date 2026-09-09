<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Detalle'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => __('Usuarios'), 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => __('Detalle')],
]);
$fullName = trim(($user->names ?? '') . ' ' . ($user->last_names ?? ''));
?>
<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Equipo') ?></span>
            <h1 class="eventic-title"><?= h($fullName ?: $user->email) ?></h1>
            <p class="eventic-subtitle"><?= h($user->email) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'pen') . ' ' . __('Editar'), ['action' => 'edit', $user->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'arrow-left') . ' ' . __('Volver'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card">
        <div class="eventic-card-heading">
            <div>
                <span class="eventic-eyebrow"><?= __('Perfil') ?></span>
                <h2><?= __('Informacion del usuario') ?></h2>
            </div>
            <span class="eventic-status <?= $user->active ? 'is-active' : 'is-muted' ?>">
                <?= $user->active ? __('Activo') : __('Inactivo') ?>
            </span>
        </div>
        <div class="eventic-audit-grid">
            <div><span><?= __('Correo electronico') ?></span><strong><?= h($user->email) ?></strong></div>
            <div><span><?= __('Rol') ?></span><strong><?= h($user->role->name ?? __('Sin rol')) ?></strong></div>
            <div><span><?= __('Super administrador') ?></span><strong><?= $user->is_superadmin ? __('Si') : __('No') ?></strong></div>
            <div><span><?= __('Creado') ?></span><strong><?= h($user->created) ?></strong></div>
            <div><span><?= __('Modificado') ?></span><strong><?= h($user->modified) ?></strong></div>
        </div>
    </div>
</section>
