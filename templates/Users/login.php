<?php $this->assign('title', __('Acceso')); ?>
<div class="eventic-login-panel">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand"><?= h(env('APP_NAME') ?: 'Eventic') ?></div>
        <div>
            <h1><?= __('Gestiona eventos, accesos y asistencia desde una sola operacion.') ?></h1>
            <p><?= __('Registros, pases digitales, validacion QR y reportes listos para coordinar equipos en sitio con una experiencia profesional.') ?></p>
            <div class="eventic-login-proof">
                <span><?= __('Pases con QR unico') ?></span>
                <span><?= __('Control de staff') ?></span>
                <span><?= __('Check-in en movil') ?></span>
            </div>
        </div>
    </section>
    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <h3 class="account-title"><?= __('Bienvenido') ?></h3>
                <p class="account-subtitle"><?= __('Accede al centro de control de tus eventos.') ?></p>
                <?php
                echo $this->Form->create(null, [
                    'spacing' => 'mb-4',
                ]);
                echo $this->Form->control('email', ['label' => __('Correo electronico'), 'placeholder' => 'admin@admin.com']);
                echo $this->Form->control('password', ['label' => __('Contrasena'), 'placeholder' => __('Ingresa tu contrasena')]);
                echo $this->Form->control('remember_me', ['type' => 'checkbox', 'label' => __('Mantener sesion iniciada')]);
                echo $this->Form->button(__('Ingresar'), ['class' => 'btn btn-primary account-btn w-100']);
                ?>
                <div class="account-footer">
                    <p><?= __('Olvido su contrasena?') ?> <?= $this->Html->link(__('Recuperarla aqui'), ['controller' => 'Users', 'action' => 'forgotPassword'], ['class' => 'btn-block-option']) ?></p>
                </div>
                <?php
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </section>
</div>
