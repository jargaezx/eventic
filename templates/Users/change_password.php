<?php
$this->assign('title', __('Cambiar contrasena'));
?>

<div class="eventic-login-panel eventic-login-panel-compact">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand">
            <img src="/assets/img/eventic-mark.svg" alt="<?= h(env('APP_NAME') ?: 'EventIC') ?>">
        </div>
        <div>
            <span class="eventic-login-kicker"><?= __('Seguridad de cuenta') ?></span>
            <h1><?= __('Actualiza tu acceso de forma segura.') ?></h1>
            <p><?= __('Mantén protegida tu cuenta para operar eventos, pases digitales y validacion QR.') ?></p>
        </div>
    </section>

    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <h3 class="account-title"><?= __('Cambiar contrasena') ?></h3>
                <p class="account-subtitle"><?= h($user->email ?? __('Ingresa tu nueva contrasena.')) ?></p>
                <?php
                echo $this->Form->create($user, ['spacing' => 'mb-4']);
                echo $this->Form->control('password', [
                    'label' => __('Nueva contrasena'),
                    'value' => '',
                    'autocomplete' => 'new-password',
                    'required' => true,
                ]);
                echo $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'label' => __('Confirmar nueva contrasena'),
                    'autocomplete' => 'new-password',
                    'required' => true,
                ]);
                echo $this->Form->button(
                    $this->FontAwesome->icon('fas', 'save') . ' ' . __('Guardar contrasena'),
                    ['class' => 'btn btn-primary account-btn w-100', 'escapeTitle' => false]
                );
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
