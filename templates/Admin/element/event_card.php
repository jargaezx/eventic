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
$cover = $event->cover ? '/' . $dir . $event->cover : null;
$cardCoverPath = ROOT . DS . (string)$event->cover_dir . 'card-' . (string)$event->cover;
$cardCover = $event->cover && is_file($cardCoverPath) ? '/' . $dir . 'card-' . $event->cover : $cover;
$statusClass = $event->active ? 'is-active' : 'is-muted';
$statusLabel = $event->active ? __('Activo') : __('Inactivo');
?>
<article class="eventic-card eventic-event-card h-100">
    <div class="eventic-event-thumb">
        <?php if ($cardCover): ?>
            <img src="<?= h($cardCover) ?>" alt="<?= h($event->name) ?>" class="eventic-event-cover" loading="lazy">
        <?php else: ?>
            <div class="eventic-event-cover eventic-event-cover-placeholder" role="img" aria-label="<?= h($event->name) ?>">
                <span><?= h(mb_substr((string)$event->name, 0, 1)) ?></span>
            </div>
        <?php endif; ?>
        <span class="eventic-status <?= $statusClass ?> eventic-event-status"><?= $statusLabel ?></span>
    </div>

    <div class="eventic-event-body">
        <div>
            <h3 class="eventic-event-name"><?= $this->Html->link(h($event->name), ['controller' => 'Events', 'action' => 'view', $event->id], ['escape' => false]) ?></h3>
            <p class="eventic-event-description"><?= h($event->description) ?></p>
            <div class="eventic-event-meta">
                <span><?= $this->FontAwesome->icon('fas', 'calendar-alt') ?> <?= h($event->event_date) ?></span>
                <span><?= $this->FontAwesome->icon('fas', 'users') ?> <?= __('{0} lugares', $event->capacity) ?></span>
            </div>
        </div>

        <div class="eventic-progress eventic-event-progress">
            <div class="d-flex justify-content-between small fw-bold">
                <span><?= __('Ocupacion') ?></span>
                <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <div class="eventic-event-stats" aria-label="<?= __('Indicadores del evento') ?>">
            <span><strong><?= $sold ?></strong><?= __('Vendidos') ?></span>
            <span><strong><?= $available ?></strong><?= __('Libres') ?></span>
            <span><strong><?= $this->Number->toPercentage($checkin, 1) ?></strong><?= __('Check-in') ?></span>
        </div>

        <div class="eventic-event-actions">
            <?= $this->Html->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-right')), ['controller' => 'Events', 'action' => 'view', $event->id], ['class' => 'btn btn-primary eventic-event-main-action', 'escape' => false]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'clipboard-list'), ['controller' => 'Events', 'action' => 'register', $event->id], ['class' => 'btn btn-outline-secondary eventic-icon-btn', 'escape' => false, 'aria-label' => __('Registrar asistentes'), 'title' => __('Registrar asistentes')]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'qrcode'), ['controller' => 'Events', 'action' => 'scan', $event->id], ['class' => 'btn btn-outline-primary eventic-icon-btn', 'escape' => false, 'aria-label' => __('Escanear pases'), 'title' => __('Escanear pases')]) ?>
            <?= $this->Html->link($this->FontAwesome->icon('fas', 'chart-bar'), ['controller' => 'Events', 'action' => 'report', $event->id], ['class' => 'btn btn-outline-secondary eventic-icon-btn', 'escape' => false, 'aria-label' => __('Ver reporte'), 'title' => __('Ver reporte')]) ?>
        </div>
    </div>
</article>
