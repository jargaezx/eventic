<?php
$this->assign('title', $access === 'staff' ? __('Acceso staff') : __('Acceso admin'));
$targetLabel = $access === 'staff' ? __('Staff') : __('Admin');
?>

<div class="eventic-login-panel">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand"><?= h(env('APP_NAME') ?: 'Eventic') ?></div>
        <div>
            <span class="eventic-login-kicker"><?= __('Plataforma de eventos') ?></span>
            <h1><?= __('Control profesional para eventos, accesos y asistencia.') ?></h1>
            <p><?= __('Administra eventos, registros, pases digitales y validacion QR con una experiencia preparada para operacion movil.') ?></p>
            <div class="eventic-login-proof">
                <span><?= __('Admin configurable') ?></span>
                <span><?= __('Staff movil') ?></span>
                <span><?= __('Pases con QR unico') ?></span>
            </div>
        </div>
    </section>

    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <div class="eventic-access-tabs" aria-label="<?= __('Tipo de acceso') ?>">
                    <?= $this->Html->link(__('Admin'), '/admin/login', [
                        'class' => 'eventic-access-tab ' . ($access === 'admin' ? 'is-active' : ''),
                    ]) ?>
                    <?= $this->Html->link(__('Staff'), '/staff/login', [
                        'class' => 'eventic-access-tab ' . ($access === 'staff' ? 'is-active' : ''),
                    ]) ?>
                </div>

                <h3 class="account-title"><?= __('Acceso {0}', $targetLabel) ?></h3>
                <p class="account-subtitle">
                    <?= $access === 'staff'
                        ? __('Ingresa al espacio operativo para consultar eventos asignados y escanear pases.')
                        : __('Ingresa al centro administrativo para configurar eventos, equipos y reportes.') ?>
                </p>

                <?php
                $loginUrl = $access === 'staff' ? '/staff/login' : '/admin/login';
                echo $this->Form->create(null, ['spacing' => 'mb-4', 'url' => $loginUrl]);
                $redirect = $this->request->getQuery('redirectUrl') ?: $this->request->getQuery('redirect');
                if (is_string($redirect) && $redirect !== '') {
                    echo $this->Form->hidden('redirectUrl', ['value' => $redirect]);
                }
                echo $this->Form->control('email', ['label' => __('Correo electronico'), 'placeholder' => 'usuario@empresa.com']);
                echo $this->Form->control('password', ['label' => __('Contrasena'), 'placeholder' => __('Ingresa tu contrasena')]);
                echo $this->Form->control('remember_me', ['type' => 'checkbox', 'label' => __('Mantener sesion iniciada')]);
                echo $this->Form->button(__('Ingresar'), ['class' => 'btn btn-primary account-btn w-100']);
                ?>
                <div class="account-footer">
                    <p><?= $this->Html->link(__('Recuperar contrasena'), ['controller' => 'Users', 'action' => 'forgotPassword'], ['class' => 'eventic-link']) ?></p>
                </div>
                <?php
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </section>
</div>
