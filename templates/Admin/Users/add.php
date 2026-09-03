<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Nuevo'));

$this->Breadcrumbs->add([
    ['title' => __('Usuarios'), 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => __('Nuevo usuario')],
]);
?>

<section class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <span class="eventic-eyebrow"><?= __('Equipo') ?></span>
            <h1><?= __('Nuevo usuario') ?></h1>
            <p><?= __('Crea accesos administrativos u operativos para trabajar eventos con seguridad.') ?></p>
        </div>
    </div>

    <?= $this->element('user_form', compact('user')) ?>
</section>
