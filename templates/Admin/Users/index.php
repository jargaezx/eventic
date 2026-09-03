<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Equipo'));

$this->Breadcrumbs->add([
    ['title' => __('Usuarios'), 'url' => ['controller' => 'Users', 'action' => 'index']],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Administración') ?></span>
            <h1><?= __('Usuarios del sistema') ?></h1>
            <p><?= __('Controla accesos, roles y disponibilidad del equipo que administra y opera eventos.') ?></p>
        </div>
        <?= $this->Html->link(
            $this->FontAwesome->icon('fas', 'user-plus') . ' ' . __('Nuevo usuario'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary', 'escape' => false]
        ) ?>
    </div>

    <div class="eventic-card mb-4">
        <?= $this->Form->create(null, [
            'valueSources' => 'query',
            'class' => 'row g-3 align-items-end',
        ]) ?>
        <div class="col-12 col-lg-5">
            <?= $this->Form->control('q', [
                'label' => __('Buscar'),
                'placeholder' => __('Nombre o correo'),
            ]) ?>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <?= $this->Form->control('role_id', [
                'label' => __('Rol'),
                'options' => $roles,
                'empty' => __('Todos'),
            ]) ?>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <?= $this->Form->control('active', [
                'label' => __('Estado'),
                'empty' => __('Todos'),
                'options' => [0 => __('Inactivo'), 1 => __('Activo')],
            ]) ?>
        </div>
        <div class="col-12 col-md-4 col-lg-2 d-grid">
            <?= $this->Form->button(__('Filtrar'), ['class' => 'btn btn-dark']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <div class="eventic-card">
        <div class="eventic-table-wrap">
            <table class="table eventic-table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('email', __('Usuario')) ?></th>
                        <th><?= $this->Paginator->sort('role.name', __('Rol')) ?></th>
                        <th><?= __('Perfil') ?></th>
                        <th><?= $this->Paginator->sort('created', __('Creado')) ?></th>
                        <th><?= $this->Paginator->sort('active', __('Estado')) ?></th>
                        <th class="text-end"><?= __('Acciones') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= h($user->email) ?></strong>
                            <div class="text-muted small"><?= h(trim(($user->names ?? '') . ' ' . ($user->last_names ?? '')) ?: __('Sin nombre')) ?></div>
                        </td>
                        <td><?= h($user->role->name ?? __('Sin rol')) ?></td>
                        <td>
                            <?php if ($user->is_superadmin): ?>
                                <span class="eventic-pill"><?= __('Super administrador') ?></span>
                            <?php else: ?>
                                <span class="eventic-pill"><?= __('Operativo') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user->created ? $user->created->i18nFormat('dd MMM yyyy') : '-' ?></td>
                        <td>
                            <span class="eventic-status <?= $user->active ? 'is-active' : 'is-muted' ?>">
                                <?= $user->active ? __('Activo') : __('Inactivo') ?>
                            </span>
                        </td>
                        <td>
                            <div class="eventic-actions justify-content-end">
                                <?= $this->Html->link(
                                    $this->FontAwesome->icon('fas', 'pencil-alt'),
                                    ['action' => 'edit', $user->id],
                                    ['class' => 'btn btn-light btn-sm', 'title' => __('Editar'), 'escape' => false]
                                ) ?>
                                <?= $this->Html->link(
                                    $this->FontAwesome->icon('fas', 'key'),
                                    ['action' => 'changePassword', $user->id],
                                    ['class' => 'btn btn-light btn-sm', 'title' => __('Cambiar contraseña'), 'escape' => false]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $this->FontAwesome->icon('fas', 'trash-alt'),
                                    ['action' => 'delete', $user->id],
                                    [
                                        'class' => 'btn btn-light btn-sm',
                                        'title' => __('Eliminar'),
                                        'escape' => false,
                                        'confirm' => __('¿Eliminar este usuario?'),
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($users->count() === 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="eventic-empty">
                                <strong><?= __('Sin usuarios') ?></strong>
                                <span><?= __('Crea usuarios para administrar eventos y operar accesos.') ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="eventic-table-footer">
            <span><?= $this->Paginator->counter(__('Mostrando {{start}} a {{end}} de {{count}}')) ?></span>
            <ul class="pagination mb-0">
                <?= $this->Paginator->numbers() ?>
            </ul>
        </div>
    </div>
</section>
