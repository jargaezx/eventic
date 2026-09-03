<?php
$this->assign('title', __('Usuarios: Cambiar Contraseña'));
$this->Breadcrumbs->add([
    ['title' => 'Usuarios', 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => 'Cambiar Contraseña']
]);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center py-4">
    <div>
        <?= $this->request->referer() ? $this->Html->link(
            __('{0} Regresar', $this->Html->icon('arrow-left-short')),
            $this->request->referer(),
            ['class' => 'btn btn-warning d-inline-flex align-items-center mb-3 mb-md-0', 'escape' => false]
        ) : ''; ?>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <!-- card -->
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= $user->email ?></h5>
            </div>
            <!-- card body -->
            <div class="card-body">
            <!-- form -->
            <?php
                echo $this->Flash->render();
                echo $this->Form->create($user);
                echo $this->Form->control('password', ['label' => __('Nueva Contraseña'), 'value'=>'']);
                echo $this->Form->control('password_confirm', ['type' => 'password', 'label' => __('Confirmar Nueva Contraseña')]);
                echo $this->Form->submit(__('Guardar'), [ 'container' => ['class' => 'd-grid'] ]);
                echo $this->Form->end();
            ?>
            </div>
        </div>
    </div>
</div>