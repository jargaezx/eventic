<?php
$this->assign('title', __('Permisos'));
$this->assign('subtitle', __('Accesos'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => __('Permisos'), 'url' => ['controller' => 'Permissions', 'action' => 'index']],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Seguridad') ?></span>
            <h1><?= __('Catálogo de permisos') ?></h1>
            <p><?= __('Administra las acciones disponibles para roles administrativos y operación en sitio.') ?></p>
        </div>
        <?= $this->Html->link(
            $this->FontAwesome->icon('fas', 'plus') . ' ' . __('Nuevo permiso'),
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
                'placeholder' => __('Nombre, módulo o acción'),
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
                        <th><?= $this->Paginator->sort('name', __('Permiso')) ?></th>
                        <th><?= __('Ruta') ?></th>
                        <th><?= __('Roles') ?></th>
                        <th><?= $this->Paginator->sort('created', __('Creado')) ?></th>
                        <th><?= $this->Paginator->sort('active', __('Estado')) ?></th>
                        <th class="text-end"><?= __('Acciones') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $permission): ?>
                    <?php
                    $routeParts = array_filter([
                        $permission->prefix ?: null,
                        $permission->controller ?: null,
                        $permission->action ?: null,
                    ]);
                    ?>
                    <tr>
                        <td>
                            <strong><?= h($permission->name) ?></strong>
                            <?php if ($permission->description): ?>
                            <div class="text-muted small"><?= h($permission->description) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="eventic-pill"><?= h(implode(' / ', $routeParts) ?: __('General')) ?></span></td>
                        <td><?= count($permission->roles ?? []) ?></td>
                        <td><?= $permission->created ? $permission->created->i18nFormat('dd MMM yyyy') : '-' ?></td>
                        <td>
                            <span class="eventic-status <?= $permission->active ? 'is-active' : 'is-muted' ?>">
                                <?= $permission->active ? __('Activo') : __('Inactivo') ?>
                            </span>
                        </td>
                        <td>
                            <div class="eventic-actions justify-content-end">
                                <?= $this->Html->link(
                                    $this->FontAwesome->icon('fas', 'pencil-alt'),
                                    ['action' => 'edit', $permission->id],
                                    ['class' => 'btn btn-light btn-sm', 'title' => __('Editar'), 'escape' => false]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $this->FontAwesome->icon('fas', 'trash-alt'),
                                    ['action' => 'delete', $permission->id],
                                    [
                                        'class' => 'btn btn-light btn-sm',
                                        'title' => __('Eliminar'),
                                        'escape' => false,
                                        'confirm' => __('¿Eliminar este permiso?'),
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($permissions->count() === 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="eventic-empty">
                                <strong><?= __('Sin permisos') ?></strong>
                                <span><?= __('Agrega permisos para controlar el acceso por módulos.') ?></span>
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
