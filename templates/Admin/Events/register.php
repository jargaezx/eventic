<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Registro'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Registro'],
]);

$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Mostrador') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Alta de asistentes y consulta de pases emitidos.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Capacidad') ?></span><strong class="eventic-kpi-value"><?= $event->capacity ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Registrados') ?></span><strong class="eventic-kpi-value"><?= $sold ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Disponibles') ?></span><strong class="eventic-kpi-value"><?= $available ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Asistencias') ?></span><strong class="eventic-kpi-value"><?= $attended ?></strong></div></div>
    </div>

    <div class="eventic-card mb-4">
        <div class="eventic-progress mb-3">
            <div class="d-flex justify-content-between fw-bold">
                <span><?= __('Ocupacion') ?></span>
                <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <?php
        echo $this->Form->create(null, [
            'url' => ['action' => 'checkout', $event->id],
            'method' => 'GET',
            'valueSources' => 'query',
            'class' => 'row gy-2 gx-2 align-items-end',
        ]);
        ?>
        <div class="col-12 col-md">
            <?= $this->Form->control('n', ['label' => __('Pases a emitir'), 'type' => 'number', 'min' => 1, 'max' => $available, 'placeholder' => __('Cantidad')]) ?>
        </div>
        <div class="col-12 col-md-auto">
            <?= $this->Form->button(__('{0} Registrar asistentes', $this->FontAwesome->icon('fas', 'user-plus')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false, 'disabled' => $available === 0]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <div class="eventic-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 mb-0"><?= __('Pases emitidos') ?></h2>
            <span class="eventic-pill"><?= __('{0} registros', $sold) ?></span>
        </div>
        <div class="eventic-table-wrap">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= __('Folio') ?></th>
                        <th><?= __('Registro') ?></th>
                        <th><?= __('Nombre') ?></th>
                        <th><?= __('Correo') ?></th>
                        <th><?= __('Asistencia') ?></th>
                        <th><?= __('Estado') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?></strong></td>
                            <td><?= h($ticket->created) ?></td>
                            <td><?= h($ticket->name) ?></td>
                            <td><?= h($ticket->email) ?></td>
                            <td><?= $ticket->attended ? h($ticket->attended) : $this->Html->badge(__('Pendiente'), ['class' => 'warning']) ?></td>
                            <td><?= $this->Html->badge($ticket->active ? __('Activo') : __('Cancelado'), ['class' => $ticket->active ? 'success' : 'light']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tickets->count()): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><?= __('No hay pases emitidos.') ?></td></tr>
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
