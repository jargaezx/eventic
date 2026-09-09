<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Cambiar contrasena'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => __('Usuarios'), 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => __('Cambiar contrasena')],
]);
?>
<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Seguridad') ?></span>
            <h1 class="eventic-title"><?= __('Cambiar contrasena') ?></h1>
            <p class="eventic-subtitle"><?= h($user->email) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(
                $this->FontAwesome->icon('fas', 'arrow-left') . ' ' . __('Volver'),
                ['action' => 'index'],
                ['class' => 'btn btn-outline-secondary', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="eventic-card eventic-form-card">
        <div class="eventic-card-heading">
            <div>
                <span class="eventic-eyebrow"><?= __('Credenciales') ?></span>
                <h2><?= __('Nueva contrasena') ?></h2>
                <p><?= __('Define una contrasena segura para este usuario.') ?></p>
            </div>
        </div>
        <?php
        echo $this->Form->create($user, ['class' => 'eventic-event-form']);
        echo $this->Form->control('password', [
            'label' => __('Contrasena'),
            'value' => '',
            'autocomplete' => 'new-password',
        ]);
        echo $this->Form->control('password_confirm', [
            'type' => 'password',
            'label' => __('Confirmar contrasena'),
            'autocomplete' => 'new-password',
        ]);
        echo $this->Form->button(
            $this->FontAwesome->icon('fas', 'save') . ' ' . __('Guardar contrasena'),
            ['class' => 'btn btn-primary', 'escapeTitle' => false]
        );
        echo $this->Form->end();
        ?>
    </div>
</section>
