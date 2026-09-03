<?php
$this->assign('title', __('Permisos'));
$this->assign('subtitle', __('Ver Permiso'));
$this->Breadcrumbs->add([
    ['title' => 'Permisos', 'url' => ['controller' => 'Permissions', 'action' => 'index']],
    ['title' => 'Ver Permiso']
]);
?>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><?= $permission->name ?></h4>
    </div>
    <div class="card-body">
        <dl class="row">

            <dt class="col-sm-3">Nombre:</dt>
            <dd class="col-sm-9"><?= $permission->name ?></dd>

            <dt class="col-sm-3">Descripción:</dt>
            <dd class="col-sm-9"><?= $permission->description ?></dd>

            <dt class="col-sm-3">Roles:</dt>
            <dd class="col-sm-9">
                <ol>
                <?php
                    foreach($permission->roles as $rol){
                        echo "<li>{$rol->name}</li>";
                    }
                ?>
                </ol>
            </dd>

            <dt class="col-sm-3">Creado:</dt>
            <dd class="col-sm-9"><?= $permission->created ?></dd>

            <dt class="col-sm-3">Modificado:</dt>
            <dd class="col-sm-9"><?= $permission->modified ?></dd>

            <dt class="col-sm-3">Activo:</dt>
            <dd class="col-sm-9"><?= $this->Html->badge($permission->active ? 'Activo':'Inactivo', ['class' => $permission->active ? 'success':'light']); ?></dd>
        </dl>
    </div>
    <!--end card-body-->
</div>
