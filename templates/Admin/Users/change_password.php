<?php
$this->assign('title', __('Usuarios'));
$this->assign('subtitle', __('Cambiar Contraseña'));

$this->Breadcrumbs->add([
    ['title' => 'Usuarios', 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => 'Cambiar Contraseña']
]);
?>
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><?= __('Cambiar Contraseña') ?></h4>
    </div>
    <div class="card-body">
        <?php
        echo $this->Form->create($user);
        echo $this->Form->control('password', ['label' => __('Contraseña'), 'value'=>'']);
        echo $this->Form->control('password_confirm', ['type'=>'password', 'label' => __('Confirmar Contraseña')]);
        echo $this->Form->submit(__('Guardar'), ['class' => 'btn btn-primary w-100']);
        echo $this->Form->end()
        ?>
    </div>

</div>