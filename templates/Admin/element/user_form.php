<?php
$isEdit = !$user->isNew();
$currentUser = $this->request->getAttribute('identity');
?>

<div class="eventic-card">
    <?= $this->Form->create($user, ['class' => 'eventic-form']) ?>
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <span class="eventic-eyebrow"><?= __('Usuario') ?></span>
            <h2 class="h4 mb-2"><?= $isEdit ? h($user->email) : __('Nuevo usuario') ?></h2>
            <p class="text-muted mb-0">
                <?= __('Gestiona identidad, rol y estado de acceso para el equipo de operación.') ?>
            </p>
        </div>
        <div class="col-12 col-lg-7">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('names', [
                        'label' => __('Nombre(s)'),
                        'placeholder' => __('María Fernanda'),
                    ]) ?>
                </div>
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('last_names', [
                        'label' => __('Apellido(s)'),
                        'placeholder' => __('García López'),
                    ]) ?>
                </div>
                <div class="col-12 col-md-7">
                    <?= $this->Form->control('email', [
                        'label' => __('Correo electrónico'),
                        'placeholder' => 'persona@empresa.com',
                    ]) ?>
                </div>
                <div class="col-12 col-md-5">
                    <?= $this->Form->control('role_id', [
                        'label' => __('Rol'),
                        'empty' => __('Selecciona un rol'),
                    ]) ?>
                </div>
                <?php if (!$isEdit): ?>
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('password', [
                        'label' => __('Contraseña'),
                    ]) ?>
                </div>
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('password_confirm', [
                        'type' => 'password',
                        'label' => __('Confirmar contraseña'),
                    ]) ?>
                </div>
                <?php endif; ?>
                <?php if ($currentUser && $currentUser->is_superadmin): ?>
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('is_superadmin', [
                        'type' => 'checkbox',
                        'label' => __('Super administrador'),
                    ]) ?>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-6">
                    <?= $this->Form->control('active', [
                        'type' => 'checkbox',
                        'label' => __('Usuario activo'),
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="eventic-form-actions">
        <?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-light']) ?>
        <?= $this->Form->button(__('Guardar usuario'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
