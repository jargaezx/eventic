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
$filters = $filters ?? ['q' => '', 'status' => 'active', 'attendance' => 'all', 'delivery' => 'all'];
$this->Paginator->options(['url' => ['?' => $filters]]);
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

    <div class="eventic-card eventic-filter-card mb-4">
        <?= $this->Form->create(null, [
            'type' => 'get',
            'valueSources' => 'query',
            'class' => 'row g-3 align-items-end',
        ]) ?>
        <div class="col-12 col-lg-4">
            <?= $this->Form->control('q', [
                'label' => __('Buscar pase'),
                'value' => $filters['q'],
                'placeholder' => __('Nombre, correo o folio'),
            ]) ?>
        </div>
        <div class="col-12 col-sm-4 col-lg-2">
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
        <div class="col-12 col-sm-4 col-lg-2">
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
        <div class="col-12 col-sm-4 col-lg-2">
            <?= $this->Form->control('delivery', [
                'label' => __('Correo'),
                'type' => 'select',
                'value' => $filters['delivery'],
                'options' => [
                    'all' => __('Todos'),
                    'sent' => __('Enviados'),
                    'not_sent' => __('Sin envio'),
                ],
            ]) ?>
        </div>
        <div class="col-12 col-lg-2 d-grid">
            <?= $this->Form->button(__('{0} Filtrar', $this->FontAwesome->icon('fas', 'filter')), ['class' => 'btn btn-outline-primary', 'escapeTitle' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
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
            <span class="eventic-pill"><?= $this->Paginator->counter(__('{{count}} visibles')) ?></span>
        </div>
        <div class="eventic-table-wrap">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= __('Folio') ?></th>
                        <th><?= __('Emitido') ?></th>
                        <th><?= __('Nombre') ?></th>
                        <th><?= __('Correo') ?></th>
                        <th><?= __('Responsable') ?></th>
                        <th><?= __('Asistencia') ?></th>
                        <th><?= __('Estado') ?></th>
                        <th><?= __('Entrega') ?></th>
                        <th class="text-end"><?= __('Acciones') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= $this->Html->link(h(str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)), ['action' => 'ticket', $event->id, $ticket->id], ['escape' => false]) ?></strong></td>
                            <td><?= h($ticket->created) ?></td>
                            <td><?= h($ticket->name) ?></td>
                            <td><?= h($ticket->email) ?></td>
                            <td><?= h($ticket->registered_by_user->full_name ?? '-') ?></td>
                            <td><?= $ticket->attended ? h($ticket->attended) : $this->Html->badge(__('Pendiente'), ['class' => 'warning']) ?></td>
                            <td><?= $this->Html->badge($ticket->active ? __('Activo') : __('Cancelado'), ['class' => $ticket->active ? 'success' : 'light']) ?></td>
                            <td>
                                <?php if ($ticket->last_emailed): ?>
                                    <span class="eventic-ticket-delivery">
                                        <?= $this->FontAwesome->icon('fas', 'paper-plane') ?>
                                        <?= h($ticket->last_emailed) ?>
                                    </span>
                                    <small><?= __('{0} envios', (int)$ticket->email_attempt_count) ?></small>
                                <?php else: ?>
                                    <?= $this->Html->badge(__('Sin confirmar'), ['class' => 'light']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="eventic-ticket-actions-cell">
                                <?php if ($ticket->active): ?>
                                    <?= $this->Form->create(null, [
                                        'url' => ['action' => 'resendTicket', $event->id, $ticket->id],
                                        'class' => 'eventic-ticket-resend',
                                    ]) ?>
                                        <?= $this->Form->control('email', [
                                            'label' => false,
                                            'type' => 'email',
                                            'value' => $ticket->email,
                                            'class' => 'form-control form-control-sm',
                                            'aria-label' => __('Correo para reenviar pase {0}', str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)),
                                        ]) ?>
                                        <?= $this->Form->button(
                                            __($this->FontAwesome->icon('fas', 'envelope') . ' Reenviar'),
                                            ['class' => 'btn btn-outline-primary btn-sm', 'escapeTitle' => false]
                                        ) ?>
                                    <?= $this->Form->end() ?>
                                    <?= $this->Form->postLink(
                                        __($this->FontAwesome->icon('fas', 'ban') . ' Cancelar'),
                                        ['action' => 'cancelTicket', $event->id, $ticket->id],
                                        [
                                            'class' => 'btn btn-outline-danger btn-sm eventic-ticket-cancel',
                                            'escape' => false,
                                            'confirm' => __('Este pase quedara cancelado y no podra utilizarse en el acceso. El cupo se liberara.'),
                                        ]
                                    ) ?>
                                <?php else: ?>
                                    <span class="eventic-ticket-cancelled" title="<?= h($ticket->cancelled_reason ?: '') ?>">
                                        <?= $this->FontAwesome->icon('fas', 'circle-xmark') ?>
                                        <?= __('Cancelado') ?>
                                    </span>
                                    <?php if ($ticket->cancelled_by_user): ?>
                                        <small><?= __('por {0}', h($ticket->cancelled_by_user->full_name)) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tickets->count()): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4"><?= __('No hay pases con esos filtros.') ?></td></tr>
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
