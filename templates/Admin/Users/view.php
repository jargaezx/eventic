<?php
$this->assign('title', __('Usarios'));
$this->assign('subtitle', __('Ver Usuario'));
$this->Breadcrumbs->add([
    ['title' => 'Usuarios', 'url' => ['controller' => 'Users', 'action' => 'index']],
    ['title' => 'Ver Usuario']
]);
?>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><?= $user->email ?></h4>
    </div>
    <div class="card-body">
        <dl class="row">

            <dt class="col-sm-3">Correo elctrónico:</dt>
            <dd class="col-sm-9"><?= $user->email ?></dd>

            <dt class="col-sm-3">Rol:</dt>
            <dd class="col-sm-9"><?= @$user->role->name ?></dd>

            <dt class="col-sm-3">Super Administrador:</dt>
            <dd class="col-sm-9"><?= $user->is_superadmin ? __('Si') : __('No') ?></dd>

            <dt class="col-sm-3">Creado:</dt>
            <dd class="col-sm-9"><?= $user->created ?></dd>

            <dt class="col-sm-3">Modificado:</dt>
            <dd class="col-sm-9"><?= $user->modified ?></dd>

            <dt class="col-sm-3">Activo:</dt>
            <dd class="col-sm-9"><?= $this->Html->badge($user->active ? 'Activo':'Inactivo', ['class' => $user->active ? 'success':'light']); ?></dd>
        </dl>
    </div>
    <!--end card-body-->
</div>
