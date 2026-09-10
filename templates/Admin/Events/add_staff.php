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
$assignedCount = count($staffByUser ?? []);
$roleCounts = array_fill_keys(array_keys($roleOptions), 0);
foreach (($staffByUser ?? []) as $staff) {
    $role = Staff::normalizeRole($staff->role ?? null);
    $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
}
$orderedUsers = [];
foreach ($users as $id => $user) {
    if (isset($staffByUser[$id])) {
        $orderedUsers[$id] = $user;
    }
}
foreach ($users as $id => $user) {
    if (!isset($orderedUsers[$id])) {
        $orderedUsers[$id] = $user;
    }
}
$assignedGroups = array_fill_keys(array_keys($roleOptions), []);
$availableUsers = [];
foreach ($orderedUsers as $id => $user) {
    if (isset($staffByUser[$id])) {
        $role = Staff::normalizeRole($staffByUser[$id]->role ?? null);
        $assignedGroups[$role][$id] = $user;
    } else {
        $availableUsers[$id] = $user;
    }
}
$userIndexes = [];
$index = 0;
foreach ($orderedUsers as $id => $user) {
    $userIndexes[$id] = $index++;
}
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

    <div class="eventic-card eventic-staff-builder-card">
        <?= $this->Form->create(null, ['class' => 'eventic-staff-matrix']) ?>
        <div class="eventic-staff-toolbar">
            <div>
                <span class="eventic-eyebrow"><?= __('Staff operativo') ?></span>
                <strong><?= __('Equipo asignado') ?></strong>
            </div>
            <div class="eventic-staff-toolbar-actions">
                <label class="eventic-staff-search">
                    <?= $this->FontAwesome->icon('fas', 'search') ?>
                    <input type="search" placeholder="<?= h(__('Buscar en el equipo')) ?>" data-staff-search>
                </label>
                <button type="button" class="btn btn-primary" data-open-staff-drawer>
                    <?= $this->FontAwesome->icon('fas', 'user-plus') ?>
                    <?= __('Agregar integrante') ?>
                </button>
            </div>
        </div>
        <div class="eventic-staff-overview" data-staff-overview>
            <div>
                <span><?= __('Equipo asignado') ?></span>
                <strong data-staff-assigned-count><?= $assignedCount ?></strong>
            </div>
            <div>
                <span><?= __('Con venta') ?></span>
                <strong data-staff-seller-count><?= (int)($roleCounts[Staff::ROLE_SELLER] ?? 0) ?></strong>
            </div>
            <div>
                <span><?= __('Con accesos') ?></span>
                <strong data-staff-access-count><?= (int)($roleCounts[Staff::ROLE_ACCESS] ?? 0) ?></strong>
            </div>
        </div>

        <div class="eventic-staff-builder">
            <section class="eventic-current-team">
                <div class="eventic-role-lanes">
                    <?php foreach ($roleOptions as $role => $label): ?>
                        <div class="eventic-role-lane <?= count($assignedGroups[$role]) === 0 ? 'is-empty' : '' ?>" data-role-lane="<?= h($role) ?>">
                            <div class="eventic-role-lane-head">
                                <strong><?= h($label) ?></strong>
                                <span data-role-count="<?= h($role) ?>"><?= count($assignedGroups[$role]) ?></span>
                            </div>
                            <div class="eventic-role-lane-list" data-role-list="<?= h($role) ?>">
                                <?php foreach ($assignedGroups[$role] as $id => $user): ?>
                                    <?php
                                    $staff = $staffByUser[$id] ?? null;
                                    $selectedRole = Staff::normalizeRole($staff->role ?? null);
                                    $defaults = Staff::roleDefaults($selectedRole);
                                    $canSell = (bool)($staff->can_register ?? $defaults['can_register']);
                                    $customPermissions = false;
                                    foreach ($permissionLabels as $field => $permissionLabel) {
                                        if ((bool)($staff->{$field} ?? $defaults[$field]) !== (bool)$defaults[$field]) {
                                            $customPermissions = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?= $this->element('staff_assignment_card', [
                                        'index' => $userIndexes[$id],
                                        'id' => $id,
                                        'user' => $user,
                                        'staff' => $staff,
                                        'isAssigned' => true,
                                        'selectedRole' => $selectedRole,
                                        'roleOptions' => $roleOptions,
                                        'permissionLabels' => $permissionLabels,
                                        'defaults' => $defaults,
                                        'customPermissions' => $customPermissions,
                                        'canSell' => $canSell,
                                        'ticketTypes' => $ticketTypes,
                                    ]) ?>
                                <?php endforeach; ?>
                            </div>
                            <p class="eventic-role-lane-empty" data-role-empty="<?= h($role) ?>" hidden></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="eventic-empty-filter" data-current-team-empty <?= $assignedCount > 0 ? 'hidden' : '' ?>><?= __('Aun no hay personal asignado a este evento.') ?></p>
            </section>
        </div>

        <div class="eventic-staff-drawer" data-staff-drawer hidden>
            <div class="eventic-staff-drawer-backdrop" data-close-staff-drawer></div>
            <aside class="eventic-available-staff" role="dialog" aria-modal="true" aria-labelledby="staff-drawer-title">
                <div class="eventic-section-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Agregar integrante') ?></span>
                        <h2 id="staff-drawer-title"><?= __('Usuarios disponibles') ?></h2>
                    </div>
                    <button type="button" class="eventic-drawer-close" data-close-staff-drawer aria-label="<?= h(__('Cerrar')) ?>">
                        <?= $this->FontAwesome->icon('fas', 'times') ?>
                    </button>
                </div>
                <label class="eventic-staff-search">
                    <?= $this->FontAwesome->icon('fas', 'search') ?>
                    <input type="search" placeholder="<?= h(__('Buscar usuario disponible')) ?>" data-available-search>
                </label>
                <div class="eventic-available-list" data-available-list>
                    <?php foreach ($availableUsers as $id => $user): ?>
                        <?php
                        $selectedRole = Staff::ROLE_ACCESS;
                        $defaults = Staff::roleDefaults($selectedRole);
                        ?>
                        <?= $this->element('staff_assignment_card', [
                            'index' => $userIndexes[$id],
                            'id' => $id,
                            'user' => $user,
                            'staff' => null,
                            'isAssigned' => false,
                            'selectedRole' => $selectedRole,
                            'roleOptions' => $roleOptions,
                            'permissionLabels' => $permissionLabels,
                            'defaults' => $defaults,
                            'customPermissions' => false,
                            'canSell' => false,
                            'ticketTypes' => $ticketTypes,
                        ]) ?>
                    <?php endforeach; ?>
                </div>
                <p class="eventic-empty-filter" data-staff-empty hidden><?= __('No hay usuarios disponibles con ese criterio.') ?></p>
            </aside>
        </div>

        <?= $this->Form->button(__('{0} Guardar equipo', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100 mt-3', 'escapeTitle' => false]) ?>
        <?= $this->Form->end() ?>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
var roleDefaults = <?= json_encode($roleDefaultMap) ?>;
var roleLabels = <?= json_encode($roleOptions) ?>;

var staffSearch = document.querySelector('[data-staff-search]');
var availableSearch = document.querySelector('[data-available-search]');
var staffEmpty = document.querySelector('[data-staff-empty]');
var availableList = document.querySelector('[data-available-list]');
var staffDrawer = document.querySelector('[data-staff-drawer]');
var openStaffDrawer = document.querySelector('[data-open-staff-drawer]');
var closeStaffDrawerButtons = document.querySelectorAll('[data-close-staff-drawer]');

function refreshStaffCounters() {
    var cards = Array.from(document.querySelectorAll('[data-staff-assignment]'));
    var assignedCards = cards.filter(function (card) {
        return card.dataset.staffAssigned === '1';
    });
    var sellerCards = assignedCards.filter(function (card) {
        return card.dataset.staffRole === 'seller';
    });
    var accessCards = assignedCards.filter(function (card) {
        return card.dataset.staffRole === 'access';
    });
    var assignedCount = document.querySelector('[data-staff-assigned-count]');
    var sellerCount = document.querySelector('[data-staff-seller-count]');
    var accessCount = document.querySelector('[data-staff-access-count]');
    if (assignedCount) assignedCount.textContent = String(assignedCards.length);
    if (sellerCount) sellerCount.textContent = String(sellerCards.length);
    if (accessCount) accessCount.textContent = String(accessCards.length);

    document.querySelectorAll('[data-role-list]').forEach(function (list) {
        var role = list.dataset.roleList;
        var count = list.querySelectorAll('[data-staff-assignment][data-staff-assigned="1"]').length;
        var roleCount = document.querySelector('[data-role-count="' + role + '"]');
        var lane = document.querySelector('[data-role-lane="' + role + '"]');
        if (roleCount) roleCount.textContent = String(count);
        if (lane) lane.classList.toggle('is-empty', count === 0);
    });

    var currentTeamEmpty = document.querySelector('[data-current-team-empty]');
    if (currentTeamEmpty) {
        currentTeamEmpty.hidden = assignedCards.length > 0;
    }
    var assignedPill = document.querySelector('[data-staff-assigned-pill]');
    if (assignedPill) {
        assignedPill.textContent = assignedCards.length + ' activos';
    }
}

document.querySelectorAll('.eventic-staff-assignment').forEach(function (card) {
    var roleSelect = card.querySelector('.eventic-role-select');
    var customToggle = card.querySelector('.eventic-custom-permissions input');
    var permissionInputs = card.querySelectorAll('[data-permission]');
    var assignToggle = card.querySelector('[data-staff-toggle]');
    var body = card.querySelector('[data-staff-body]');
    var roleSummary = card.querySelector('[data-staff-role-summary]');
    var permissionGrid = card.querySelector('[data-permissions]');
    var salesSettings = card.querySelectorAll('[data-sales-settings]');
    var editButton = card.querySelector('[data-staff-edit]');
    var toggleLabel = card.querySelector('[data-staff-toggle-label]');

    function moveToCurrentContainer() {
        var target = assignToggle.checked
            ? document.querySelector('[data-role-list="' + roleSelect.value + '"]')
            : availableList;
        if (target && card.parentElement !== target) {
            target.appendChild(card);
        }
    }

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
        var wasAvailable = card.parentElement === availableList;
        body.hidden = !assignToggle.checked || !card.classList.contains('is-editing');
        card.classList.toggle('is-assigned', assignToggle.checked);
        card.classList.toggle('is-available', !assignToggle.checked);
        card.dataset.staffAssigned = assignToggle.checked ? '1' : '0';
        card.dataset.staffRole = roleSelect.value;
        if (roleSummary) {
            roleSummary.textContent = assignToggle.checked ? (roleLabels[roleSelect.value] || 'Asignado') : 'Disponible para asignar';
        }
        if (toggleLabel) {
            toggleLabel.textContent = assignToggle.checked ? 'Asignado' : 'Asignar';
        }
        if (editButton) {
            editButton.hidden = !assignToggle.checked;
        }
        updateSalesVisibility();
        moveToCurrentContainer();
        if (assignToggle.checked && wasAvailable && staffDrawer && !staffDrawer.hidden) {
            staffDrawer.hidden = true;
            document.body.classList.remove('eventic-drawer-open');
            card.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
        refreshStaffCounters();
        applyStaffSearch();
        applyAvailableSearch();
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
        moveToCurrentContainer();
        updateAssignedState();
    }

    roleSelect.addEventListener('change', applyRoleDefaults);
    customToggle.addEventListener('change', function () {
        updatePermissionVisibility();
        applyRoleDefaults();
    });
    assignToggle.addEventListener('change', function () {
        card.classList.toggle('is-editing', assignToggle.checked);
        updateAssignedState();
    });
    editButton?.addEventListener('click', function () {
        card.classList.toggle('is-editing');
        body.hidden = !card.classList.contains('is-editing');
    });
    permissionInputs.forEach(function (input) {
        input.addEventListener('change', updateSalesVisibility);
    });
    updatePermissionVisibility();
    updateAssignedState();
    applyRoleDefaults();
});

function applyStaffSearch() {
    var query = (staffSearch?.value || '').trim().toLowerCase();
    document.querySelectorAll('[data-role-list] [data-staff-assignment]').forEach(function (card) {
        var matches = !query || (card.dataset.staffName || '').includes(query);
        card.hidden = !matches;
    });
    refreshStaffCounters();
}

staffSearch?.addEventListener('input', applyStaffSearch);

function applyAvailableSearch() {
    var query = (availableSearch?.value || '').trim().toLowerCase();
    var visibleCount = 0;
    availableList?.querySelectorAll('[data-staff-assignment]').forEach(function (card) {
        var matches = !query || (card.dataset.staffName || '').includes(query);
        card.hidden = !matches;
        if (!card.hidden) {
            visibleCount++;
        }
    });
    if (staffEmpty) {
        staffEmpty.hidden = visibleCount > 0;
    }
}

availableSearch?.addEventListener('input', applyAvailableSearch);

openStaffDrawer?.addEventListener('click', function () {
    staffDrawer.hidden = false;
    document.body.classList.add('eventic-drawer-open');
    availableSearch?.focus();
    applyAvailableSearch();
});

closeStaffDrawerButtons.forEach(function (button) {
    button.addEventListener('click', function () {
        staffDrawer.hidden = true;
        document.body.classList.remove('eventic-drawer-open');
    });
});

refreshStaffCounters();
applyStaffSearch();
applyAvailableSearch();
<?php $this->Html->scriptEnd(); ?>
