<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul class="sidebar-vertical">
                <li class="menu-title">
                    <span><?= __('Operacion') ?></span>
                </li>
                <li><?= $this->RBAC->link($this->FontAwesome->icon('fas', 'chart-line') . '<span>' . __('Panel') . '</span>', ['controller'=>'Users', 'action'=>'dashboard'], ['escape' => false]) ?></li>
                <li><?= $this->RBAC->link($this->FontAwesome->icon('fas', 'calendar-days') . '<span>' . __('Eventos') . '</span>', ['controller'=>'Events', 'action'=>'index'], ['escape' => false]) ?></li>
                <li><?= $this->Html->link($this->FontAwesome->icon('fas', 'mobile-screen-button') . '<span>' . __('Modo staff') . '</span>', ['prefix'=>'Staff', 'controller'=>'Events', 'action'=>'index'], ['escape' => false]) ?></li>
                <li class="menu-title">
                    <span><?= __('Administracion') ?></span>
                </li>
                <li><?= $this->RBAC->link($this->FontAwesome->icon('fas', 'users') . '<span>' . __('Equipo') . '</span>', ['controller'=>'Users', 'action'=>'index'], ['escape' => false]) ?></li>
                <li><?= $this->RBAC->link($this->FontAwesome->icon('fas', 'user-shield') . '<span>' . __('Roles') . '</span>', ['controller'=>'Roles', 'action'=>'index'], ['escape' => false]) ?></li>
                <li><?= $this->RBAC->link($this->FontAwesome->icon('fas', 'key') . '<span>' . __('Permisos') . '</span>', ['controller'=>'Permissions', 'action'=>'index'], ['escape' => false]) ?></li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
