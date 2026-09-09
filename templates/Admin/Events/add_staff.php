<?php
use App\Model\Entity\Staff;
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Equipo'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Equipo'],
]);

$permissionLabels = [
    'can_manage_event' => __('Editar evento'),
    'can_manage_staff' => __('Gestionar equipo'),
    'can_register' => __('Registro / venta'),
    'can_scan' => __('Accesos QR'),
    'can_view_reports' => __('Reportes'),
];
$roleDefaultMap = [];
foreach (array_keys($roleOptions) as $role) {
    $roleDefaultMap[$role] = Staff::roleDefaults($role);
}
$ticketTypes = $ticketTypes ?? [];
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Equipo del evento') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Asigna roles, permisos operativos y cuotas de emision por tipo de boleto.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card mb-4">
        <div class="eventic-card-heading">
            <div>
                <span class="eventic-eyebrow"><?= __('Roles disponibles') ?></span>
                <h2><?= __('Control operativo por evento') ?></h2>
                <p><?= __('Cada usuario puede tener un rol base y permisos ajustados a su responsabilidad real dentro del evento.') ?></p>
            </div>
        </div>
        <div class="eventic-role-grid">
            <?php foreach ($roleOptions as $role => $label): ?>
                <div>
                    <strong><?= h($label) ?></strong>
                    <span><?= h($roleDescriptions[$role] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="eventic-card">
        <?= $this->Form->create(null, ['class' => 'eventic-staff-matrix']) ?>
        <?php $i = 0; ?>
        <?php foreach ($users as $id => $user): ?>
            <?php
            $staff = $staffByUser[$id] ?? null;
            $selectedRole = Staff::normalizeRole($staff->role ?? null);
            $defaults = Staff::roleDefaults($selectedRole);
            $customPermissions = false;
            foreach ($permissionLabels as $field => $label) {
                if ((bool)($staff->{$field} ?? $defaults[$field]) !== (bool)$defaults[$field]) {
                    $customPermissions = true;
                    break;
                }
            }
            ?>
            <article class="eventic-staff-assignment">
                <div class="eventic-staff-assignment-head">
                    <div>
                        <strong><?= h($user) ?></strong>
                        <span><?= $staff ? h($staff->role_label ?: $roleOptions[$selectedRole]) : __('Sin asignar') ?></span>
                    </div>
                    <label class="eventic-switch">
                        <?= $this->Form->checkbox("users.{$i}.id", [
                            'value' => $id,
                            'checked' => !empty($staff),
                            'hiddenField' => false,
                        ]) ?>
                        <span><?= __('Asignar') ?></span>
                    </label>
                </div>

                <div class="eventic-staff-assignment-grid">
                    <div>
                        <?= $this->Form->control("users.{$i}._joinData.role", [
                            'label' => __('Rol operativo'),
                            'options' => $roleOptions,
                            'value' => $selectedRole,
                            'class' => 'eventic-role-select',
                        ]) ?>
                    </div>
                    <div>
                        <?= $this->Form->control("users.{$i}._joinData.sales_limit", [
                            'label' => __('Limite global'),
                            'type' => 'number',
                            'min' => 0,
                            'value' => $staff->sales_limit ?? null,
                            'placeholder' => __('Sin limite'),
                        ]) ?>
                    </div>
                    <div>
                        <label class="eventic-switch eventic-custom-permissions">
                            <?= $this->Form->checkbox("users.{$i}._joinData.custom_permissions", [
                                'checked' => $customPermissions,
                                'hiddenField' => false,
                            ]) ?>
                            <span><?= __('Personalizar permisos') ?></span>
                        </label>
                    </div>
                </div>

                <div class="eventic-permission-grid" data-permissions>
                    <?php foreach ($permissionLabels as $field => $label): ?>
                        <label class="eventic-permission-chip">
                            <?= $this->Form->checkbox("users.{$i}._joinData.{$field}", [
                                'checked' => (bool)($staff->{$field} ?? $defaults[$field]),
                                'hiddenField' => false,
                                'data-permission' => $field,
                            ]) ?>
                            <span><?= h($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($ticketTypes): ?>
                    <?php
                    $limitsByType = [];
                    foreach ($staff->staff_ticket_type_limits ?? [] as $limit) {
                        $limitsByType[$limit->ticket_type_id] = $limit;
                    }
                    ?>
                    <div class="eventic-staff-type-limits">
                        <div>
                            <span class="eventic-eyebrow"><?= __('Cuotas por tipo') ?></span>
                            <p><?= __('Deja vacio cuando el usuario pueda emitir sin limite para ese tipo.') ?></p>
                        </div>
                        <div class="eventic-staff-type-limit-grid">
                            <?php foreach ($ticketTypes as $type): ?>
                                <?php $limit = $limitsByType[$type->id] ?? null; ?>
                                <label>
                                    <span>
                                        <strong><?= h($type->name) ?></strong>
                                        <small><?= $type->capacity === null ? __('Cupo general') : __('Cupo {0}', (int)$type->capacity) ?></small>
                                    </span>
                                    <?= $this->Form->number("users.{$i}._joinData.ticket_type_limits.{$type->id}.sales_limit", [
                                        'min' => 0,
                                        'value' => $limit && $limit->active ? $limit->sales_limit : null,
                                        'placeholder' => __('Sin limite'),
                                        'class' => 'form-control',
                                    ]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
            <?php $i++; ?>
        <?php endforeach; ?>

        <?= $this->Form->button(__('{0} Guardar equipo', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100 mt-3', 'escapeTitle' => false]) ?>
        <?= $this->Form->end() ?>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
var roleDefaults = <?= json_encode($roleDefaultMap) ?>;

document.querySelectorAll('.eventic-staff-assignment').forEach(function (card) {
    var roleSelect = card.querySelector('.eventic-role-select');
    var customToggle = card.querySelector('.eventic-custom-permissions input');
    var permissionInputs = card.querySelectorAll('[data-permission]');

    function applyRoleDefaults() {
        if (customToggle.checked) {
            return;
        }
        var defaults = roleDefaults[roleSelect.value] || {};
        permissionInputs.forEach(function (input) {
            input.checked = Boolean(defaults[input.dataset.permission]);
        });
    }

    roleSelect.addEventListener('change', applyRoleDefaults);
    customToggle.addEventListener('change', applyRoleDefaults);
    applyRoleDefaults();
});
<?php $this->Html->scriptEnd(); ?>
