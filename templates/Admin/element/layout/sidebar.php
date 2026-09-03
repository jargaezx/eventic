<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul class="sidebar-vertical">
                <li class="menu-title">
                    <span><?= __('Operacion') ?></span>
                </li>
                <li><?= $this->RBAC->link(__('Panel'), ['controller'=>'Users', 'action'=>'dashboard']) ?></li>
                <li><?= $this->RBAC->link(__('Eventos'), ['controller'=>'Events', 'action'=>'index']) ?></li>
                <li><?= $this->Html->link(__('Modo staff'), ['prefix'=>'Staff', 'controller'=>'Events', 'action'=>'index']) ?></li>
                <li class="menu-title">
                    <span><?= __('Administracion') ?></span>
                </li>
                <li><?= $this->RBAC->link(__('Equipo'), ['controller'=>'Users', 'action'=>'index']) ?></li>
                <li><?= $this->RBAC->link(__('Roles'), ['controller'=>'Roles', 'action'=>'index']) ?></li>
                <li><?= $this->RBAC->link(__('Permisos'), ['controller'=>'Permissions', 'action'=>'index']) ?></li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
