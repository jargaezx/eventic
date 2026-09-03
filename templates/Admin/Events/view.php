<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Detalle'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Detalle'],
]);

$sold = (int)$event->ticket_count;
$capacity = max(1, (int)$event->capacity);
$attended = (int)$event->ticket_attended_count;
$available = max(0, (int)$event->capacity - $sold);
$occupancy = round(($sold / $capacity) * 100, 1);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
$dir = preg_replace('#^webroot/#', '', str_replace('\\', '/', (string)$event->cover_dir));
$cover = '/' . $dir . $event->cover;
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Evento') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= h($event->description) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Registrar', $this->FontAwesome->icon('fas', 'clipboard-list')), ['action' => 'register', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Reporte', $this->FontAwesome->icon('fas', 'chart-bar')), ['action' => 'report', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Capacidad') ?></span><strong class="eventic-kpi-value"><?= $event->capacity ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Registrados') ?></span><strong class="eventic-kpi-value"><?= $sold ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Disponibles') ?></span><strong class="eventic-kpi-value"><?= $available ?></strong></div></div>
        <div class="col-6 col-xl-3"><div class="eventic-kpi"><span><?= __('Check-in') ?></span><strong class="eventic-kpi-value"><?= $this->Number->toPercentage($checkin, 1) ?></strong></div></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="eventic-card mb-4">
                <img src="<?= h($cover) ?>" alt="<?= h($event->name) ?>" class="eventic-event-cover mb-3">
                <div class="eventic-progress">
                    <div class="d-flex justify-content-between fw-bold">
                        <span><?= __('Ocupacion') ?></span>
                        <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="row g-2 mt-3">
                    <div class="col-md-4"><span class="eventic-pill w-100"><?= __('Fecha: {0}', h($event->event_date)) ?></span></div>
                    <div class="col-md-4"><span class="eventic-pill w-100"><?= __('Responsable: {0}', h($event->owner->full_name ?? '-')) ?></span></div>
                    <div class="col-md-4"><span class="eventic-pill w-100"><?= $event->active ? __('Evento activo') : __('Evento inactivo') ?></span></div>
                </div>
            </div>

            <div class="eventic-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0"><?= __('Staff operativo') ?></h2>
                    <?= $this->RBAC->link(__('{0} Gestionar', $this->FontAwesome->icon('fas', 'users-cog')), ['action' => 'addStaff', $event->id], ['class' => 'btn btn-outline-secondary btn-sm', 'escape' => false]) ?>
                </div>
                <div class="eventic-table-wrap">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><?= __('Nombre') ?></th>
                                <th><?= __('Registro') ?></th>
                                <th><?= __('Escaneo') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($event->users as $user): ?>
                                <tr>
                                    <td><?= h($user->full_name) ?></td>
                                    <td><?= $this->Html->badge($user->_joinData->register ? __('Permitido') : __('No'), ['class' => $user->_joinData->register ? 'success' : 'light']) ?></td>
                                    <td><?= $this->Html->badge($user->_joinData->scan ? __('Permitido') : __('No'), ['class' => $user->_joinData->scan ? 'success' : 'light']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$event->users): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4"><?= __('Sin staff asignado.') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="eventic-card mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0"><?= __('Pase digital') ?></h2>
                    <?= $this->RBAC->link(__('{0} Editar QR', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'editQR', $event->id], ['class' => 'btn btn-outline-secondary btn-sm', 'escape' => false]) ?>
                </div>
                <?php if ($ticketPreview): ?>
                    <img src="<?= $ticketPreview ?>" alt="<?= __('Vista previa del boleto') ?>" class="img-fluid rounded">
                <?php else: ?>
                    <div class="eventic-empty"><?= __('Configura una plantilla valida para previsualizar el pase.') ?></div>
                <?php endif; ?>
            </div>

            <div class="eventic-card">
                <h2 class="h5 mb-3"><?= __('Configuracion del pase') ?></h2>
                <div class="eventic-table-wrap">
                    <table class="table align-middle">
                        <tbody>
                            <tr><th><?= __('Moneda') ?></th><td class="text-end"><?= h($event->currency ?? 'MXN') ?></td></tr>
                            <tr><th><?= __('Pago') ?></th><td class="text-end"><?= __('Sin costo') ?></td></tr>
                            <tr><th><?= __('Color primario') ?></th><td class="text-end"><?= h($event->primary_color ?? '-') ?></td></tr>
                            <tr><th><?= __('Color acento') ?></th><td class="text-end"><?= h($event->accent_color ?? '-') ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <?= $this->RBAC->link(__('{0} Editar evento', $this->FontAwesome->icon('fas', 'pen')), ['action' => 'edit', $event->id], ['class' => 'btn btn-secondary w-100 mt-3', 'escape' => false]) ?>
            </div>
        </div>
    </div>
</div>
