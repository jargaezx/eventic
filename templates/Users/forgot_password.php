<?php
$this->assign('title', __('Recuperar contrasena'));
?>

<div class="eventic-login-panel eventic-login-panel-compact">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand"><?= h(env('APP_NAME') ?: 'Eventic') ?></div>
        <div>
            <span class="eventic-login-kicker"><?= __('Seguridad de cuenta') ?></span>
            <h1><?= __('Recupera el acceso a tu consola Eventic.') ?></h1>
            <p><?= __('Te enviaremos un enlace seguro para restablecer tu contrasena y volver a operar tus eventos.') ?></p>
        </div>
    </section>

    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <h3 class="account-title"><?= __('Recuperar contrasena') ?></h3>
                <p class="account-subtitle"><?= __('Ingresa el correo asociado a tu cuenta.') ?></p>
                <?php
                echo $this->Form->create(null, ['spacing' => 'mb-4']);
                echo $this->Form->control('email', [
                    'label' => __('Correo electronico'),
                    'placeholder' => 'usuario@empresa.com',
                ]);
                echo $this->Form->button(__('Enviar enlace'), ['class' => 'btn btn-primary account-btn w-100']);
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
