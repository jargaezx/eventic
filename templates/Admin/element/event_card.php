<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 */
$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
$dir = preg_replace('#^webroot/#', '', str_replace('\\', '/', (string)$event->cover_dir));
$cover = '/' . $dir . $event->cover;
?>
<article class="eventic-card eventic-event-card h-100">
    <img src="<?= h($cover) ?>" alt="<?= h($event->name) ?>" class="eventic-event-cover">
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div>
            <h3><?= $this->Html->link(h($event->name), ['controller' => 'Events', 'action' => 'view', $event->id], ['escape' => false]) ?></h3>
            <p><?= h($event->description) ?></p>
        </div>
        <?= $this->Html->badge($event->active ? __('Activo') : __('Inactivo'), ['class' => $event->active ? 'success' : 'light']) ?>
    </div>
    <div class="eventic-progress">
        <div class="d-flex justify-content-between small fw-bold">
            <span><?= __('Registro') ?></span>
            <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
    <div class="row g-2 text-center">
        <div class="col"><span class="eventic-pill"><?= __('{0} vendidos', $sold) ?></span></div>
        <div class="col"><span class="eventic-pill"><?= __('{0} libres', $available) ?></span></div>
        <div class="col"><span class="eventic-pill"><?= __('{0} check-in', $this->Number->toPercentage($checkin, 1)) ?></span></div>
    </div>
    <div class="eventic-actions mt-3">
        <?= $this->Html->link(__('{0} Registrar', $this->FontAwesome->icon('fas', 'clipboard-list')), ['controller' => 'Events', 'action' => 'register', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
        <?= $this->Html->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['controller' => 'Events', 'action' => 'scan', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
        <?= $this->Html->link(__('{0} Reporte', $this->FontAwesome->icon('fas', 'chart-bar')), ['controller' => 'Events', 'action' => 'report', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
    </div>
</article>
