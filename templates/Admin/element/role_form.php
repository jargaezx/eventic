<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $rol
 * @var array $permissionsByModule
 * @var bool $isEdit
 */
$selected = collection($rol->permissions ?? [])->extract('id')->toList();
$selected = array_fill_keys($selected, true);
$isEdit = $isEdit ?? false;
?>
<?= $this->Form->create($rol) ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="eventic-card">
            <h2 class="h5 mb-3"><?= __('Informacion del rol') ?></h2>
            <?= $this->Form->control('name', ['label' => __('Nombre')]) ?>
            <?= $this->Form->control('description', ['label' => __('Descripcion'), 'rows' => 4]) ?>
            <?php if ($isEdit): ?>
                <?= $this->Form->control('active', ['label' => __('Rol activo')]) ?>
            <?php endif; ?>
            <?= $this->Form->button(__('{0} Guardar rol', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false]) ?>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="eventic-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0"><?= __('Permisos') ?></h2>
                <span class="eventic-pill"><?= __('{0} modulos', count($permissionsByModule)) ?></span>
            </div>
            <?php foreach ($permissionsByModule as $module => $permissions): ?>
                <section class="eventic-permission-group">
                    <h3><?= h($module) ?></h3>
                    <div class="row g-2">
                        <?php foreach ($permissions as $permission): ?>
                            <div class="col-12 col-md-6">
                                <label class="eventic-permission-option">
                                    <input type="checkbox" name="permissions[_ids][]" value="<?= h($permission->id) ?>" <?= isset($selected[$permission->id]) ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= h($permission->name) ?></strong>
                                        <small><?= h($permission->description ?: $permission->action) ?></small>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->Form->end() ?>
