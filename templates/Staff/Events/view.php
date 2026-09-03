<?php
$this->assign('title', __('Operacion'));
$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$available = max(0, (int)$event->capacity - $sold);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
?>
<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Evento') ?></div>
    <h1><?= h($event->name) ?></h1>
    <p><?= h($event->event_date) ?></p>
</section>

<div class="row g-3 mb-4">
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Registrados') ?></span><strong class="eventic-kpi-value"><?= $sold ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Asistieron') ?></span><strong class="eventic-kpi-value"><?= $attended ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Pendientes') ?></span><strong class="eventic-kpi-value"><?= max(0, $sold - $attended) ?></strong></div></div>
    <div class="col-6"><div class="eventic-kpi"><span><?= __('Disponibles') ?></span><strong class="eventic-kpi-value"><?= $available ?></strong></div></div>
</div>

<div class="eventic-staff-card">
    <div class="eventic-progress mb-3">
        <div class="d-flex justify-content-between fw-bold">
            <span><?= __('Check-in confirmado') ?></span>
            <span><?= $this->Number->toPercentage($checkin, 1) ?></span>
        </div>
        <div class="progress"><div class="progress-bar bg-success" style="width: <?= h($checkin) ?>%"></div></div>
    </div>
    <div class="eventic-actions">
        <?= $this->Html->link(__('{0} Iniciar escaneo', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary w-100', 'escape' => false]) ?>
        <?= $this->Html->link(__('{0} Mis eventos', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'index'], ['class' => 'btn btn-outline-primary w-100', 'escape' => false]) ?>
    </div>
</div>
