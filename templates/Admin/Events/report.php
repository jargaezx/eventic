<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Reporte'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Reporte'],
]);

$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$pending = max(0, $sold - $attended);
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Reporte operativo') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Resumen de registro, asistencia y pases pendientes.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Balance Excel', $this->FontAwesome->icon('fas', 'file-excel')), ['action' => 'exportSales', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Asistencia Excel', $this->FontAwesome->icon('fas', 'file-download')), ['action' => 'exportAttendance', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Registrados') ?></span><strong class="eventic-kpi-value"><?= $sold ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Asistieron') ?></span><strong class="eventic-kpi-value"><?= $attended ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Pendientes') ?></span><strong class="eventic-kpi-value"><?= $pending ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Disponibles') ?></span><strong class="eventic-kpi-value"><?= $available ?></strong></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="eventic-card">
                <h2 class="h5 mb-3"><?= __('Ocupacion') ?></h2>
                <div class="eventic-progress">
                    <div class="d-flex justify-content-between fw-bold">
                        <span><?= __('Pases emitidos') ?></span>
                        <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="eventic-card">
                <h2 class="h5 mb-3"><?= __('Ingreso') ?></h2>
                <div class="eventic-progress">
                    <div class="d-flex justify-content-between fw-bold">
                        <span><?= __('Check-in confirmado') ?></span>
                        <span><?= $this->Number->toPercentage($checkin, 1) ?></span>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: <?= h($checkin) ?>%" role="progressbar" aria-valuenow="<?= h($checkin) ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="eventic-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 mb-0"><?= __('Pases') ?></h2>
            <span class="eventic-pill"><?= __('{0} registros', $sold) ?></span>
        </div>
        <div class="eventic-table-wrap">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= __('Folio') ?></th>
                        <th><?= __('Nombre') ?></th>
                        <th><?= __('Correo') ?></th>
                        <th><?= __('Registrado por') ?></th>
                        <th><?= __('Emitido') ?></th>
                        <th><?= __('Asistencia') ?></th>
                        <th><?= __('Escaneado por') ?></th>
                        <th><?= __('Estado') ?></th>
                        <th><?= __('Cancelado por') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?></strong></td>
                            <td><?= h($ticket->name) ?></td>
                            <td><?= h($ticket->email) ?></td>
                            <td><?= h($ticket->registered_by_user->full_name ?? '-') ?></td>
                            <td><?= h($ticket->created) ?></td>
                            <td><?= $ticket->attended ? h($ticket->attended) : $this->Html->badge(__('Pendiente'), ['class' => 'warning']) ?></td>
                            <td><?= h($ticket->checked_in_user->full_name ?? '-') ?></td>
                            <td><?= $this->Html->badge($ticket->active ? __('Activo') : __('Cancelado'), ['class' => $ticket->active ? 'success' : 'light']) ?></td>
                            <td><?= h($ticket->cancelled_by_user->full_name ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tickets->count()): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4"><?= __('No hay pases emitidos.') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($tickets->count()): ?>
            <div class="mt-3">
                <?= $this->Paginator->numbers() ?>
                <?= $this->Paginator->prev(__('Anterior')) ?>
                <?= $this->Paginator->next(__('Siguiente')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
