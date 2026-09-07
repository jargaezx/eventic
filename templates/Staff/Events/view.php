<?php
$this->assign('title', __('Operacion'));
$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$pending = max(0, $sold - $attended);
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
?>

<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Evento asignado') ?></div>
    <h1><?= h($event->name) ?></h1>
    <p><?= $event->event_date ? h($event->event_date->i18nFormat('dd MMM yyyy, HH:mm')) : __('Fecha por definir') ?></p>
</section>

<div class="row g-3 mb-4">
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Capacidad') ?></span><strong class="eventic-kpi-value"><?= (int)$event->capacity ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Registrados') ?></span><strong class="eventic-kpi-value"><?= $sold ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Asistieron') ?></span><strong class="eventic-kpi-value"><?= $attended ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Pendientes') ?></span><strong class="eventic-kpi-value"><?= $pending ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Disponibles') ?></span><strong class="eventic-kpi-value"><?= $available ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Ocupacion') ?></span><strong class="eventic-kpi-value"><?= $this->Number->toPercentage($occupancy, 1) ?></strong></div></div>
</div>

<div class="eventic-staff-card mb-3">
    <div class="eventic-section-header">
        <h2><?= __('Avance de acceso') ?></h2>
        <span class="eventic-pill"><?= $this->Number->toPercentage($checkin, 1) ?></span>
    </div>
    <div class="progress eventic-progress mb-3">
        <div class="progress-bar bg-success" style="width: <?= h($checkin) ?>%"></div>
    </div>
    <div class="eventic-actions">
        <?= $this->Html->link(__('{0} Iniciar escaneo', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary w-100', 'escape' => false]) ?>
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
                        <th><?= __('Estado') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($event->tickets as $ticket): ?>
                    <tr>
                        <td><strong><?= h($ticket->name) ?></strong></td>
                        <td><?= h($ticket->email) ?></td>
                        <td>
                            <span class="eventic-status <?= $ticket->attended ? 'is-active' : 'is-muted' ?>">
                                <?= $ticket->attended ? __('Validado') : __('Pendiente') ?>
                            </span>
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
