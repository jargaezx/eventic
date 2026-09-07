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
            $occupancy = round(($sold / $capacity) * 100, 1);
            ?>
            <article class="eventic-staff-card">
                <div>
                    <h2><?= h($event->name) ?></h2>
                    <p><?= h($event->event_date) ?></p>
                </div>
                <div class="eventic-progress">
                    <div class="d-flex justify-content-between small fw-bold">
                        <span><?= __('Registro') ?></span>
                        <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: <?= h($occupancy) ?>%"></div></div>
                </div>
                <div class="eventic-actions">
                    <?= $this->Html->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
                    <?= $this->Html->link(__('{0} Ver', $this->FontAwesome->icon('fas', 'chart-bar')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
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
