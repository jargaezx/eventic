<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Editar'));

$this->Breadcrumbs->add([
    ['title' => __('Usuarios'), 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => __('Editar usuario')],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Equipo') ?></span>
            <h1><?= h($user->email) ?></h1>
            <p><?= __('Actualiza datos de acceso, rol y estado del usuario.') ?></p>
        </div>
        <?= $this->Html->link(
            $this->FontAwesome->icon('fas', 'key') . ' ' . __('Cambiar contraseña'),
            ['action' => 'changePassword', $user->id],
            ['class' => 'btn btn-light', 'escape' => false]
        ) ?>
    </div>

    <?= $this->element('user_form', compact('user')) ?>
</section>
