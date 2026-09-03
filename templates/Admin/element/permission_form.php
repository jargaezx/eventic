<?php
$isEdit = !$permission->isNew();
?>

<div class="eventic-card">
    <?= $this->Form->create($permission, ['class' => 'eventic-form']) ?>
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <span class="eventic-eyebrow"><?= __('Permiso') ?></span>
            <h2 class="h4 mb-2"><?= $isEdit ? h($permission->name) : __('Nuevo permiso') ?></h2>
            <p class="text-muted mb-0">
                <?= __('Registra acciones autorizables por módulo para controlar cada flujo del sistema.') ?>
            </p>
        </div>
        <div class="col-12 col-lg-7">
            <div class="row g-3">
                <div class="col-12">
                    <?= $this->Form->control('name', [
                        'label' => __('Nombre visible'),
                        'placeholder' => __('Escanear accesos'),
                    ]) ?>
                </div>
                <div class="col-12">
                    <?= $this->Form->control('description', [
                        'label' => __('Descripción'),
                        'rows' => 3,
                        'placeholder' => __('Permite validar pases digitales de asistentes.'),
                    ]) ?>
                </div>
                <div class="col-12 col-md-4">
                    <?= $this->Form->control('prefix', [
                        'label' => __('Prefijo'),
                        'placeholder' => 'Admin',
                    ]) ?>
                </div>
                <div class="col-12 col-md-4">
                    <?= $this->Form->control('controller', [
                        'label' => __('Controlador'),
                        'placeholder' => 'Events',
                    ]) ?>
                </div>
                <div class="col-12 col-md-4">
                    <?= $this->Form->control('action', [
                        'label' => __('Acción'),
                        'placeholder' => 'scan',
                    ]) ?>
                </div>
                <?php if ($isEdit): ?>
                <div class="col-12">
                    <?= $this->Form->control('active', [
                        'type' => 'checkbox',
                        'label' => __('Permiso activo'),
                    ]) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="eventic-form-actions">
        <?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-light']) ?>
        <?= $this->Form->button(__('Guardar permiso'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
