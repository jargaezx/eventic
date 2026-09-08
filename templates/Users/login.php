<?php
$this->assign('title', $access === 'staff' ? __('Acceso staff') : __('Acceso admin'));
$targetLabel = $access === 'staff' ? __('Staff') : __('Admin');
$targetIntro = $access === 'staff'
    ? __('Ingresa al espacio operativo para consultar eventos asignados y escanear pases.')
    : __('Ingresa al centro administrativo para configurar eventos, equipos y reportes.');
?>

<div class="eventic-login-panel">
    <section class="eventic-login-hero">
        <div class="eventic-login-brand">
            <img src="/img/logo.png" alt="<?= h(env('APP_NAME') ?: 'Eventic') ?>">
        </div>
        <div>
            <span class="eventic-login-kicker"><?= __('Suite de operacion para eventos') ?></span>
            <h1><?= __('Eventos, pases y accesos bajo control.') ?></h1>
            <p><?= __('Gestiona registros, equipos, venta de boletos y validacion QR desde una plataforma clara, rapida y preparada para operacion movil.') ?></p>
            <div class="eventic-login-proof">
                <span><?= $this->FontAwesome->icon('fas', 'sliders-h') ?> <?= __('Configuracion avanzada') ?></span>
                <span><?= $this->FontAwesome->icon('fas', 'mobile-alt') ?> <?= __('Operacion movil') ?></span>
                <span><?= $this->FontAwesome->icon('fas', 'qrcode') ?> <?= __('Acceso QR seguro') ?></span>
            </div>
        </div>
    </section>

    <section class="eventic-login-card">
        <div class="account-box">
            <div class="account-wrapper">
                <div class="eventic-access-tabs" aria-label="<?= __('Tipo de acceso') ?>">
                    <?= $this->Html->link(__('Admin'), '/admin/login', [
                        'class' => 'eventic-access-tab ' . ($access === 'admin' ? 'is-active' : ''),
                        'aria-current' => $access === 'admin' ? 'page' : null,
                    ]) ?>
                    <?= $this->Html->link(__('Staff'), '/staff/login', [
                        'class' => 'eventic-access-tab ' . ($access === 'staff' ? 'is-active' : ''),
                        'aria-current' => $access === 'staff' ? 'page' : null,
                    ]) ?>
                </div>

                <span class="eventic-login-form-kicker"><?= __('Inicio de sesion') ?></span>
                <h3 class="account-title"><?= __('Acceso {0}', $targetLabel) ?></h3>
                <p class="account-subtitle"><?= $targetIntro ?></p>

                <?php
                $loginUrl = $access === 'staff' ? '/staff/login' : '/admin/login';
                echo $this->Form->create(null, ['spacing' => 'mb-4', 'url' => $loginUrl]);
                $redirect = $this->request->getQuery('redirectUrl') ?: $this->request->getQuery('redirect');
                $redirectPath = is_string($redirect) ? (parse_url($redirect, PHP_URL_PATH) ?: '') : '';
                if ($redirectPath !== '' && !str_contains($redirectPath, '/login')) {
                    echo $this->Form->hidden('redirectUrl', ['value' => $redirectPath]);
                }
                echo $this->Form->control('email', ['label' => __('Correo electronico'), 'placeholder' => 'usuario@empresa.com', 'autocomplete' => 'username', 'required' => true]);
                echo $this->Form->control('password', ['label' => __('Contrasena'), 'placeholder' => __('Ingresa tu contrasena'), 'autocomplete' => 'current-password', 'required' => true]);
                echo $this->Form->control('remember_me', ['type' => 'checkbox', 'label' => __('Mantener sesion iniciada')]);
                echo $this->Form->button($this->FontAwesome->icon('fas', 'arrow-right-to-bracket') . ' ' . __('Ingresar'), ['class' => 'btn btn-primary account-btn w-100', 'escapeTitle' => false]);
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
