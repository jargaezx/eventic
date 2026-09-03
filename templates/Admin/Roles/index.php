<?php
$this->assign('title', __('Roles'));
$this->assign('subtitle', __('Accesos'));

$this->Breadcrumbs->add([
    ['title' => __('Roles'), 'url' => ['controller' => 'Roles', 'action' => 'index']],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Administración') ?></span>
            <h1><?= __('Roles y permisos') ?></h1>
            <p><?= __('Define los perfiles de acceso para administradores, operadores y personal del evento.') ?></p>
        </div>
        <?= $this->Html->link(
            $this->FontAwesome->icon('fas', 'plus') . ' ' . __('Nuevo rol'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary', 'escape' => false]
        ) ?>
    </div>

    <div class="eventic-card mb-4">
        <?= $this->Form->create(null, [
            'valueSources' => 'query',
            'class' => 'row g-3 align-items-end',
        ]) ?>
        <div class="col-12 col-lg-6">
            <?= $this->Form->control('q', [
                'label' => __('Buscar'),
                'placeholder' => __('Nombre o descripción'),
            ]) ?>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <?= $this->Form->control('active', [
                'label' => __('Estado'),
                'empty' => __('Todos'),
                'options' => [0 => __('Inactivo'), 1 => __('Activo')],
            ]) ?>
        </div>
        <div class="col-12 col-md-8 col-lg-3 d-grid">
            <?= $this->Form->button(__('Filtrar'), ['class' => 'btn btn-dark']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <div class="eventic-card">
        <div class="eventic-table-wrap">
            <table class="table eventic-table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('name', __('Rol')) ?></th>
                        <th><?= $this->Paginator->sort('description', __('Descripción')) ?></th>
                        <th><?= __('Permisos') ?></th>
                        <th><?= $this->Paginator->sort('created', __('Creado')) ?></th>
                        <th><?= $this->Paginator->sort('active', __('Estado')) ?></th>
                        <th class="text-end"><?= __('Acciones') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $rol): ?>
                    <tr>
                        <td>
                            <strong><?= h($rol->name) ?></strong>
                        </td>
                        <td><?= h($rol->description ?: __('Sin descripción')) ?></td>
                        <td>
                            <span class="eventic-pill"><?= count($rol->permissions ?? []) ?> <?= __('permisos') ?></span>
                        </td>
                        <td><?= $rol->created ? $rol->created->i18nFormat('dd MMM yyyy') : '-' ?></td>
                        <td>
                            <span class="eventic-status <?= $rol->active ? 'is-active' : 'is-muted' ?>">
                                <?= $rol->active ? __('Activo') : __('Inactivo') ?>
                            </span>
                        </td>
                        <td>
                            <div class="eventic-actions justify-content-end">
                                <?= $this->Html->link(
                                    $this->FontAwesome->icon('fas', 'pencil-alt'),
                                    ['action' => 'edit', $rol->id],
                                    ['class' => 'btn btn-light btn-sm', 'title' => __('Editar'), 'escape' => false]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $this->FontAwesome->icon('fas', 'trash-alt'),
                                    ['action' => 'delete', $rol->id],
                                    [
                                        'class' => 'btn btn-light btn-sm',
                                        'title' => __('Eliminar'),
                                        'escape' => false,
                                        'confirm' => __('¿Eliminar este rol?'),
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($roles->count() === 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="eventic-empty">
                                <strong><?= __('Sin roles') ?></strong>
                                <span><?= __('Crea el primer rol para organizar permisos operativos.') ?></span>
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
