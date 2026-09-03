<?php
$this->assign('title', __('Roles'));
$this->assign('subtitle', __('Ver Rol'));
$this->Breadcrumbs->add([
    ['title' => 'Roles', 'url' => ['controller' => 'Roles', 'action' => 'index']],
    ['title' => 'Ver Rol']
]);
?>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><?= $rol->name ?></h4>
    </div>
    <div class="card-body">
        <dl class="row">

            <dt class="col-sm-3">Nombre:</dt>
            <dd class="col-sm-9"><?= $rol->name ?></dd>

            <dt class="col-sm-3">Descripción:</dt>
            <dd class="col-sm-9"><?= $rol->description ?></dd>

            <dt class="col-sm-3">Creado:</dt>
            <dd class="col-sm-9"><?= $rol->created ?></dd>

            <dt class="col-sm-3">Modificado:</dt>
            <dd class="col-sm-9"><?= $rol->modified ?></dd>

            <dt class="col-sm-3">Activo:</dt>
            <dd class="col-sm-9"><?= $this->Html->badge($rol->active ? 'Activo':'Inactivo', ['class' => $rol->active ? 'success':'light']); ?></dd>
        </dl>
    </div>
    <!--end card-body-->
</div>
