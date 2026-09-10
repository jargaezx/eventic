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
        <div class="eventic-staff-toolbar">
            <div>
                <span class="eventic-eyebrow"><?= __('Asignacion') ?></span>
                <strong><?= __('Selecciona solo al personal operativo') ?></strong>
                <p><?= __('Activa un usuario para configurar su rol. Las cuotas de venta solo aparecen cuando el rol puede registrar pases.') ?></p>
            </div>
            <label class="eventic-staff-search">
                <?= $this->FontAwesome->icon('fas', 'search') ?>
                <input type="search" placeholder="<?= h(__('Buscar usuario')) ?>" data-staff-search>
            </label>
        </div>
        <?php $i = 0; ?>
        <?php foreach ($users as $id => $user): ?>
            <?php
            $staff = $staffByUser[$id] ?? null;
            $isAssigned = !empty($staff);
            $selectedRole = Staff::normalizeRole($staff->role ?? null);
            $defaults = Staff::roleDefaults($selectedRole);
            $canSell = (bool)($staff->can_register ?? $defaults['can_register']);
            $customPermissions = false;
            foreach ($permissionLabels as $field => $label) {
                if ((bool)($staff->{$field} ?? $defaults[$field]) !== (bool)$defaults[$field]) {
                    $customPermissions = true;
                    break;
                }
            }
            ?>
            <article class="eventic-staff-assignment <?= $isAssigned ? 'is-assigned' : '' ?>" data-staff-assignment data-staff-name="<?= h(mb_strtolower((string)$user)) ?>">
                <div class="eventic-staff-assignment-head">
                    <div>
                        <strong><?= h($user) ?></strong>
                        <span data-staff-role-summary><?= $staff ? h($staff->role_label ?: $roleOptions[$selectedRole]) : __('Disponible para asignar') ?></span>
                    </div>
                    <label class="eventic-switch">
                        <?= $this->Form->checkbox("users.{$i}.id", [
                            'value' => $id,
                            'checked' => $isAssigned,
                            'hiddenField' => false,
                            'data-staff-toggle' => true,
                        ]) ?>
                        <span><?= __('Asignar') ?></span>
                    </label>
                </div>

                <div class="eventic-staff-assignment-body" data-staff-body <?= $isAssigned ? '' : 'hidden' ?>>
                    <div class="eventic-staff-assignment-grid">
                        <div>
                            <?= $this->Form->control("users.{$i}._joinData.role", [
                                'label' => __('Rol operativo'),
                                'options' => $roleOptions,
                                'value' => $selectedRole,
                                'class' => 'eventic-role-select',
                            ]) ?>
                        </div>
                        <div class="eventic-sales-settings" data-sales-settings <?= $canSell ? '' : 'hidden' ?>>
                            <?= $this->Form->control("users.{$i}._joinData.sales_limit", [
                                'label' => __('Limite global de venta'),
                                'type' => 'number',
                                'min' => 0,
                                'value' => $staff->sales_limit ?? null,
                                'placeholder' => __('Sin limite'),
                                'help' => __('Opcional. Aplica solo a usuarios que registran o venden pases.'),
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

                    <div class="eventic-permission-grid" data-permissions <?= $customPermissions ? '' : 'hidden' ?>>
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
                        <div class="eventic-staff-type-limits" data-sales-settings <?= $canSell ? '' : 'hidden' ?>>
                            <div>
                                <span class="eventic-eyebrow"><?= __('Cuotas por tipo') ?></span>
                                <p><?= __('Opcional para puntos de venta. Deja vacio cuando pueda emitir sin limite dentro de la disponibilidad del evento.') ?></p>
                            </div>
                            <div class="eventic-staff-type-limit-grid">
                                <?php foreach ($ticketTypes as $type): ?>
                                    <?php $limit = $limitsByType[$type->id] ?? null; ?>
                                    <label>
                                        <span>
                                            <strong><?= h($type->name) ?></strong>
                                            <small><?= __('{0} boletos del tipo', (int)$type->capacity) ?></small>
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
                </div>
            </article>
            <?php $i++; ?>
        <?php endforeach; ?>

        <?= $this->Form->button(__('{0} Guardar equipo', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100 mt-3', 'escapeTitle' => false]) ?>
        <?= $this->Form->end() ?>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
var roleDefaults = <?= json_encode($roleDefaultMap) ?>;
var roleLabels = <?= json_encode($roleOptions) ?>;

var staffSearch = document.querySelector('[data-staff-search]');

document.querySelectorAll('.eventic-staff-assignment').forEach(function (card) {
    var roleSelect = card.querySelector('.eventic-role-select');
    var customToggle = card.querySelector('.eventic-custom-permissions input');
    var permissionInputs = card.querySelectorAll('[data-permission]');
    var assignToggle = card.querySelector('[data-staff-toggle]');
    var body = card.querySelector('[data-staff-body]');
    var roleSummary = card.querySelector('[data-staff-role-summary]');
    var permissionGrid = card.querySelector('[data-permissions]');
    var salesSettings = card.querySelectorAll('[data-sales-settings]');

    function canRegister() {
        var registerInput = card.querySelector('[data-permission="can_register"]');
        return registerInput ? registerInput.checked : Boolean((roleDefaults[roleSelect.value] || {}).can_register);
    }

    function updateSalesVisibility() {
        var show = assignToggle.checked && canRegister();
        salesSettings.forEach(function (section) {
            section.hidden = !show;
        });
    }

    function updateAssignedState() {
        body.hidden = !assignToggle.checked;
        card.classList.toggle('is-assigned', assignToggle.checked);
        if (roleSummary) {
            roleSummary.textContent = assignToggle.checked ? (roleLabels[roleSelect.value] || 'Asignado') : 'Disponible para asignar';
        }
        updateSalesVisibility();
    }

    function updatePermissionVisibility() {
        permissionGrid.hidden = !customToggle.checked;
    }

    function applyRoleDefaults() {
        if (customToggle.checked) {
            updateSalesVisibility();
            return;
        }
        var defaults = roleDefaults[roleSelect.value] || {};
        permissionInputs.forEach(function (input) {
            input.checked = Boolean(defaults[input.dataset.permission]);
        });
        updateAssignedState();
    }

    roleSelect.addEventListener('change', applyRoleDefaults);
    customToggle.addEventListener('change', function () {
        updatePermissionVisibility();
        applyRoleDefaults();
    });
    assignToggle.addEventListener('change', updateAssignedState);
    permissionInputs.forEach(function (input) {
        input.addEventListener('change', updateSalesVisibility);
    });
    updatePermissionVisibility();
    updateAssignedState();
    applyRoleDefaults();
});

staffSearch?.addEventListener('input', function () {
    var query = staffSearch.value.trim().toLowerCase();
    document.querySelectorAll('.eventic-staff-assignment').forEach(function (card) {
        var matches = !query || (card.dataset.staffName || '').includes(query);
        card.hidden = !matches;
    });
});
<?php $this->Html->scriptEnd(); ?>
