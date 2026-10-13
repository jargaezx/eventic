<?php
use App\Model\Entity\Staff;
$groups = [];
foreach ($event->staffs as $member) {
    if (!$member->can_register) {
        $groups[Staff::normalizeRole($member->role)][] = $member;
    }
}
$points = $allocation['points'];
?>
<section class="eventic-allocation-summary" aria-label="<?= h(__('Puntos de venta y disponibilidad')) ?>">
    <h3><?= __('Puntos de venta') ?></h3>
    <div class="eventic-table-wrap">
        <table class="table align-middle">
            <thead><tr><th><?= __('Responsable') ?></th><th><?= __('Asignados') ?></th><th><?= __('Emitidos') ?></th><th><?= __('Por emitir') ?></th></tr></thead>
            <tbody>
                <?php foreach ($event->staffs as $member): ?>
                    <?php if (!$member->can_register) { continue; } $point = $points[(string)$member->user_id]; ?>
                    <tr>
                        <td><strong><?= h($member->user->full_name ?? '-') ?></strong>
                            <small class="d-block text-muted"><?= h($member->role_label) ?></small>
                            <?php foreach ($point['types'] as $typeQuota): ?>
                                <small class="d-block text-muted"><?= h($allocation['types'][$typeQuota['id']]['name'] ?? __('Tipo')) ?>: <?= __('{0} asignados, {1} emitidos', $typeQuota['assigned'], $typeQuota['sold']) ?></small>
                            <?php endforeach; ?>
                        </td>
                        <td><?= $point['has_quota'] ? (int)$point['assigned'] : __('Solo cupo libre') ?></td>
                        <td><?= (int)$point['sold'] ?></td>
                        <td><?= (int)$point['reserved'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$points): ?><tr><td colspan="4" class="text-muted"><?= __('Sin puntos de venta activos.') ?></td></tr><?php endif; ?>
            </tbody>
            <tfoot><tr><th><?= __('Total puntos de venta') ?></th><th><?= (int)$allocation['assigned'] ?></th><th><?= array_sum(array_column($points, 'sold')) ?></th><th><?= (int)$allocation['reserved'] ?></th></tr></tfoot>
        </table>
    </div>
    <dl class="eventic-allocation-balance">
        <div><dt><?= __('Capacidad del evento') ?></dt><dd><?= (int)$event->capacity ?></dd></div>
        <div><dt><?= __('Emitidos por puntos de venta') ?></dt><dd><?= array_sum(array_column($points, 'sold')) ?></dd></div>
        <div><dt><?= __('Emitidos fuera de estos puntos de venta') ?></dt><dd><?= (int)$allocation['other_sold'] ?></dd></div>
        <div><dt><?= __('Reservados por emitir') ?></dt><dd><?= (int)$allocation['reserved'] ?></dd></div>
        <div class="eventic-allocation-free"><dt><?= __('Libres para asignar o emitir') ?></dt><dd><?= max(0, (int)$allocation['free']) ?></dd></div>
    </dl>
    <details class="eventic-allocation-types">
        <summary><?= __('Ver disponibilidad por tipo de boleto') ?></summary>
        <div class="eventic-table-wrap">
            <table class="table align-middle">
                <thead><tr><th><?= __('Tipo') ?></th><th><?= __('Emitidos') ?></th><th><?= __('Reservados') ?></th><th><?= __('Libres') ?></th></tr></thead>
                <tbody><?php foreach ($allocation['types'] as $type): ?><tr><td><?= h($type['name']) ?></td><td><?= (int)$type['sold'] ?></td><td><?= (int)$type['reserved'] ?></td><td><?= max(0, (int)$type['free']) ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    </details>
</section>
<?php foreach ($groups as $role => $members): ?>
    <section class="eventic-team-role-group">
        <h3><?= h(Staff::roleOptions()[$role]) ?> <span class="text-muted">(<?= count($members) ?>)</span></h3>
        <ul class="list-unstyled">
            <?php foreach ($members as $member): ?>
                <li><strong><?= h($member->user->full_name ?? '-') ?></strong>
                    <div class="eventic-permission-summary">
                        <?php foreach (['can_manage_event' => __('Evento'), 'can_manage_staff' => __('Equipo'), 'can_scan' => __('Accesos'), 'can_view_reports' => __('Reportes')] as $permission => $label): ?>
                            <?php if ($member->{$permission}): ?><span><?= h($label) ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
