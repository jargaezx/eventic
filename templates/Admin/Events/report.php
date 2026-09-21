<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Reporte'));
$this->assign('eventicPage', '1');
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
$filters = $filters ?? ['q' => '', 'status' => 'active', 'attendance' => 'all', 'delivery' => 'all', 'type' => 'all'];
$ticketTypes = $ticketTypes ?? [];
$ticketTypeOptions = is_object($ticketTypes) && method_exists($ticketTypes, 'toArray') ? $ticketTypes->toArray() : (array)$ticketTypes;
$exportQuery = ['?' => $filters];
$this->Paginator->options(['url' => ['?' => $filters]]);
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Reporte operativo') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Resumen de registro, asistencia y pases pendientes.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Balance Excel', $this->FontAwesome->icon('fas', 'file-excel')), ['action' => 'exportSales', $event->id] + $exportQuery, ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Asistencia Excel', $this->FontAwesome->icon('fas', 'file-download')), ['action' => 'exportAttendance', $event->id] + $exportQuery, ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card eventic-filter-card mb-4">
        <?= $this->Form->create(null, [
            'type' => 'get',
            'valueSources' => 'query',
            'class' => 'row g-3 align-items-end',
        ]) ?>
        <div class="col-12 col-lg-3">
            <?= $this->Form->control('q', [
                'label' => __('Buscar pase'),
                'value' => $filters['q'],
                'placeholder' => __('Nombre, correo o folio'),
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <?= $this->Form->control('status', [
                'label' => __('Estado'),
                'type' => 'select',
                'value' => $filters['status'],
                'options' => [
                    'active' => __('Activos'),
                    'cancelled' => __('Cancelados'),
                    'all' => __('Todos'),
                ],
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <?= $this->Form->control('attendance', [
                'label' => __('Asistencia'),
                'type' => 'select',
                'value' => $filters['attendance'],
                'options' => [
                    'all' => __('Todas'),
                    'pending' => __('Pendientes'),
                    'checked' => __('Escaneados'),
                ],
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <?= $this->Form->control('delivery', [
                'label' => __('Correo'),
                'type' => 'select',
                'value' => $filters['delivery'],
                'options' => [
                    'all' => __('Todos'),
                    'sent' => __('Enviados'),
                    'not_sent' => __('Sin envío'),
                ],
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <?= $this->Form->control('type', [
                'label' => __('Tipo'),
                'type' => 'select',
                'value' => $filters['type'],
                'options' => ['all' => __('Todos')] + $ticketTypeOptions,
            ]) ?>
        </div>
        <div class="col-12 col-lg-1 d-grid">
            <?= $this->Form->button(__('{0} Filtrar', $this->FontAwesome->icon('fas', 'filter')), ['class' => 'btn btn-outline-primary', 'escapeTitle' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
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
                <h2 class="h5 mb-3"><?= __('Ocupación') ?></h2>
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
            <span class="eventic-pill"><?= $this->Paginator->counter(__('{{count}} visibles')) ?></span>
        </div>
        <div class="eventic-table-wrap">
            <table class="table table-hover align-middle eventic-responsive-table">
                <thead>
                    <tr>
                        <th><?= __('Folio') ?></th>
                        <th><?= __('Nombre') ?></th>
                        <th><?= __('Correo') ?></th>
                        <th><?= __('Tipo de boleto') ?></th>
                        <th><?= __('Importe') ?></th>
                        <th><?= __('Registrado por') ?></th>
                        <th><?= __('Emitido') ?></th>
                        <th><?= __('Asistencia') ?></th>
                        <th><?= __('Escaneado por') ?></th>
                        <th><?= __('Estado') ?></th>
                        <th><?= __('Último correo') ?></th>
                        <th><?= __('Cancelado') ?></th>
                        <th><?= __('Cancelado por') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td data-label="<?= h(__('Folio')) ?>"><strong><?= $this->Html->link(h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)), ['action' => 'ticket', $event->id, $ticket->id], ['escape' => false]) ?></strong></td>
                            <td data-label="<?= h(__('Nombre')) ?>"><?= h($ticket->name) ?></td>
                            <td data-label="<?= h(__('Correo')) ?>"><?= h($ticket->email) ?></td>
                            <td data-label="<?= h(__('Tipo de boleto')) ?>"><?= h($ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? '-')) ?></td>
                            <td data-label="<?= h(__('Importe')) ?>"><?= $this->Number->currency((float)$ticket->price, $ticket->currency ?: ($event->currency ?: 'MXN')) ?></td>
                            <td data-label="<?= h(__('Registrado por')) ?>"><?= h($ticket->registered_by_user->full_name ?? '-') ?></td>
                            <td data-label="<?= h(__('Emitido')) ?>"><?= h($ticket->created) ?></td>
                            <td data-label="<?= h(__('Asistencia')) ?>"><?= $ticket->attended ? h($ticket->attended) : $this->Html->badge(__('Pendiente'), ['class' => 'warning']) ?></td>
                            <td data-label="<?= h(__('Escaneado por')) ?>"><?= h($ticket->checked_in_user->full_name ?? '-') ?></td>
                            <td data-label="<?= h(__('Estado')) ?>"><?= $this->Html->badge($ticket->active ? __('Activo') : __('Cancelado'), ['class' => $ticket->active ? 'success' : 'light']) ?></td>
                            <td data-label="<?= h(__('Último correo')) ?>"><?= $ticket->last_emailed ? h($ticket->last_emailed) : '-' ?></td>
                            <td data-label="<?= h(__('Cancelado')) ?>"><?= $ticket->cancelled ? h($ticket->cancelled) : '-' ?></td>
                            <td data-label="<?= h(__('Cancelado por')) ?>"><?= h($ticket->cancelled_by_user->full_name ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tickets->count()): ?>
                        <tr class="eventic-empty-row"><td colspan="13" class="text-center text-muted py-4"><?= __('No hay pases emitidos.') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($tickets->count()): ?>
            <div class="eventic-table-footer">
                <span><?= $this->Paginator->counter(__('Mostrando {{start}} a {{end}} de {{count}}')) ?></span>
                <ul class="pagination mb-0">
                    <?= $this->Paginator->prev(__('Anterior')) ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next(__('Siguiente')) ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
