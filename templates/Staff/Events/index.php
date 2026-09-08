<?php
$this->assign('title', __('Acceso Staff'));
?>
<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Operacion en sitio') ?></div>
    <h1><?= __('Eventos asignados') ?></h1>
    <p><?= __('Selecciona el evento para consultar la operacion o iniciar validacion de pases.') ?></p>
</section>

<?php if (!$events->isEmpty()): ?>
    <div class="eventic-staff-list">
        <?php foreach ($events as $event): ?>
            <?php
            $sold = (int)$event->ticket_count;
            $capacity = max(1, (int)$event->capacity);
            $attended = (int)$event->ticket_attended_count;
            $available = max(0, (int)$event->capacity - $sold);
            $occupancy = round(($sold / $capacity) * 100, 1);
            $checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
            $eventDate = $event->event_date ? $event->event_date->i18nFormat('dd MMM yyyy, HH:mm') : __('Fecha por definir');
            $assignment = $assignments[$event->id] ?? null;
            $canScan = !$assignment || $assignment->can_scan || $assignment->scan;
            ?>
            <article class="eventic-staff-card eventic-staff-event-card">
                <div class="eventic-staff-event-head">
                    <div>
                        <span class="eventic-eyebrow"><?= h($assignment->role_label ?? __('Evento asignado')) ?></span>
                        <h2><?= h($event->name) ?></h2>
                        <p><?= h($eventDate) ?></p>
                    </div>
                    <span class="eventic-status <?= $event->active ? 'is-active' : 'is-muted' ?>">
                        <?= $event->active ? __('Activo') : __('Inactivo') ?>
                    </span>
                </div>
                <div class="eventic-staff-mini-stats">
                    <span><strong><?= $sold ?></strong><?= __('Registros') ?></span>
                    <span><strong><?= $attended ?></strong><?= __('Accesos') ?></span>
                    <span><strong><?= $available ?></strong><?= __('Libres') ?></span>
                </div>
                <div class="eventic-progress eventic-staff-card-progress">
                    <div class="d-flex justify-content-between small fw-bold">
                        <span><?= __('Ocupacion') ?></span>
                        <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: <?= h($occupancy) ?>%"></div></div>
                </div>
                <div class="eventic-progress eventic-staff-card-progress">
                    <div class="d-flex justify-content-between small fw-bold">
                        <span><?= __('Check-in') ?></span>
                        <span><?= $this->Number->toPercentage($checkin, 1) ?></span>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: <?= h($checkin) ?>%"></div></div>
                </div>
                <div class="eventic-actions">
                    <?php if ($canScan): ?>
                        <?= $this->Html->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
                    <?php endif; ?>
                    <?= $this->Html->link(__('{0} Resumen', $this->FontAwesome->icon('fas', 'chart-bar')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="eventic-empty">
        <strong><?= __('Sin eventos asignados') ?></strong>
        <span><?= __('Cuando seas agregado al staff de un evento aparecera aqui.') ?></span>
    </div>
<?php endif; ?>
