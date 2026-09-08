<?php
$this->assign('title', __('Operacion'));
$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$pending = max(0, $sold - $attended);
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
$kpis = [
    ['icon' => 'users', 'label' => __('Capacidad'), 'value' => (int)$event->capacity],
    ['icon' => 'ticket-alt', 'label' => __('Registrados'), 'value' => $sold],
    ['icon' => 'user-check', 'label' => __('Asistieron'), 'value' => $attended],
    ['icon' => 'hourglass-half', 'label' => __('Pendientes'), 'value' => $pending],
    ['icon' => 'chair', 'label' => __('Disponibles'), 'value' => $available],
    ['icon' => 'chart-pie', 'label' => __('Ocupacion'), 'value' => $this->Number->toPercentage($occupancy, 1)],
];
$canScan = !$assignment || $assignment->can_scan || $assignment->scan;
?>

<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Evento asignado') ?></div>
    <h1><?= h($event->name) ?></h1>
    <p><?= $event->event_date ? h($event->event_date->i18nFormat('dd MMM yyyy, HH:mm')) : __('Fecha por definir') ?></p>
</section>

<div class="row g-3 mb-4">
    <?php foreach ($kpis as $kpi): ?>
        <div class="col-6">
            <div class="eventic-kpi">
                <span class="eventic-kpi-icon"><?= $this->FontAwesome->icon('fas', $kpi['icon']) ?></span>
                <span class="eventic-kpi-copy">
                    <span><?= $kpi['label'] ?></span>
                    <strong class="eventic-kpi-value"><?= $kpi['value'] ?></strong>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="eventic-staff-card mb-3">
    <div class="eventic-section-header">
        <h2><?= __('Avance de acceso') ?></h2>
        <span class="eventic-pill"><?= $this->Number->toPercentage($checkin, 1) ?></span>
    </div>
    <div class="progress eventic-progress mb-3">
        <div class="progress-bar bg-success" style="width: <?= h($checkin) ?>%"></div>
    </div>
    <div class="eventic-actions eventic-staff-primary-actions">
        <?php if ($canScan): ?>
            <?= $this->Html->link(__('{0} Iniciar escaneo', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary w-100', 'escape' => false]) ?>
        <?php endif; ?>
        <?= $this->Html->link(__('{0} Mis eventos', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'index'], ['class' => 'btn btn-outline-primary w-100', 'escape' => false]) ?>
    </div>
</div>

<div class="eventic-staff-card">
    <div class="eventic-section-header">
        <h2><?= __('Asistentes') ?></h2>
        <span class="eventic-pill"><?= __('Ultimos 50') ?></span>
    </div>
    <?php if (!empty($event->tickets)): ?>
        <div class="eventic-table-wrap">
            <table class="table eventic-table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= __('Nombre') ?></th>
                        <th><?= __('Correo') ?></th>
                        <th><?= __('Folio') ?></th>
                        <th><?= __('Estado') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($event->tickets as $ticket): ?>
                    <tr>
                        <td><strong><?= h($ticket->name) ?></strong></td>
                        <td><?= h($ticket->email) ?></td>
                        <td><?= h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?></td>
                        <td>
                            <span class="eventic-status <?= $ticket->attended ? 'is-active' : 'is-muted' ?>">
                                <?= $ticket->attended ? __('Validado') : __('Pendiente') ?>
                            </span>
                            <?php if ($ticket->attended): ?>
                                <div class="small text-muted mt-1"><?= h($ticket->attended->i18nFormat('dd MMM yyyy, HH:mm')) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="eventic-empty">
            <strong><?= __('Sin asistentes') ?></strong>
            <span><?= __('Cuando existan registros apareceran aqui para consulta del staff.') ?></span>
        </div>
    <?php endif; ?>
</div>
