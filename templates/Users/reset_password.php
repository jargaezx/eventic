<?php
$this->assign('title', __('Restablecer contrasena'));
?>

<div class="eventic-login-panel eventic-login-panel-compact">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand">
            <img src="/assets/img/eventic-mark.svg" alt="<?= h(env('APP_NAME') ?: 'EventIC') ?>">
        </div>
        <div>
            <span class="eventic-login-kicker"><?= __('Seguridad de cuenta') ?></span>
            <h1><?= __('Define una nueva contrasena segura.') ?></h1>
            <p><?= __('Actualiza tus credenciales para continuar administrando eventos, pases y accesos en EventIC.') ?></p>
        </div>
    </section>

    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <h3 class="account-title"><?= __('Restablecer contrasena') ?></h3>
                <p class="account-subtitle"><?= __('Ingresa y confirma tu nueva contrasena.') ?></p>
                <?php
                echo $this->Form->create($user, ['spacing' => 'mb-4']);
                echo $this->Form->control('password', [
                    'label' => __('Contrasena'),
                    'value' => '',
                    'autocomplete' => 'new-password',
                    'required' => true,
                ]);
                echo $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'label' => __('Confirmar contrasena'),
                    'autocomplete' => 'new-password',
                    'required' => true,
                ]);
                echo $this->Form->button(__('Guardar contrasena'), ['class' => 'btn btn-primary account-btn w-100']);
                ?>
                <div class="account-footer">
                    <p><?= $this->Html->link(__('Volver al acceso'), ['controller' => 'Users', 'action' => 'login'], ['class' => 'eventic-link']) ?></p>
                </div>
                <?php
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </section>
</div>
