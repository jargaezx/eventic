<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Detalle'));
$this->assign('eventicPage', '1');
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
$cover = $event->cover ? '/' . $dir . $event->cover : null;
$eventDate = $event->event_date ? $event->event_date->i18nFormat('dd MMM yyyy, HH:mm') : '-';
$kpis = [
    ['icon' => 'users', 'label' => __('Capacidad'), 'value' => $event->capacity],
    ['icon' => 'ticket-alt', 'label' => __('Registrados'), 'value' => $sold],
    ['icon' => 'chair', 'label' => __('Disponibles'), 'value' => $available],
    ['icon' => 'user-check', 'label' => __('Check-in'), 'value' => $this->Number->toPercentage($checkin, 1)],
];
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
        <?php foreach ($kpis as $kpi): ?>
            <div class="col-6 col-xl-3">
                <div class="eventic-kpi">
                    <span class="eventic-kpi-icon"><?= $this->FontAwesome->icon('fas', $kpi['icon']) ?></span>
                    <span class="eventic-kpi-copy">
                        <span><?= $kpi['label'] ?></span>
                        <strong class="eventic-kpi-value"><?= $kpi['value'] ?></strong>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="eventic-card eventic-detail-card mb-4">
                <?php if ($cover): ?>
                    <img src="<?= h($cover) ?>" alt="<?= h($event->name) ?>" class="eventic-detail-cover mb-3">
                <?php else: ?>
                    <div class="eventic-detail-cover eventic-event-cover-placeholder mb-3" role="img" aria-label="<?= h($event->name) ?>">
                        <span><?= h(mb_substr((string)$event->name, 0, 1)) ?></span>
                    </div>
                <?php endif; ?>
                <div class="eventic-progress">
                    <div class="d-flex justify-content-between fw-bold">
                        <span><?= __('Ocupacion') ?></span>
                        <span><?= $this->Number->toPercentage($occupancy, 1) ?></span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= h($occupancy) ?>%" role="progressbar" aria-valuenow="<?= h($occupancy) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="eventic-detail-meta mt-3">
                    <div>
                        <span><?= __('Fecha') ?></span>
                        <strong><?= h($eventDate) ?></strong>
                    </div>
                    <div>
                        <span><?= __('Responsable') ?></span>
                        <strong><?= h($event->owner->full_name ?? '-') ?></strong>
                    </div>
                    <div>
                        <span><?= __('Estado') ?></span>
                        <strong><?= $event->active ? __('Activo') : __('Inactivo') ?></strong>
                    </div>
                </div>
            </div>

            <div class="eventic-card">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Equipo') ?></span>
                        <h2><?= __('Staff operativo') ?></h2>
                    </div>
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
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Acceso') ?></span>
                        <h2><?= __('Pase digital') ?></h2>
                    </div>
                    <?= $this->RBAC->link(__('{0} Editar QR', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'editQR', $event->id], ['class' => 'btn btn-outline-secondary btn-sm', 'escape' => false]) ?>
                </div>
                <?php if ($ticketPreview): ?>
                    <div class="eventic-ticket-preview">
                        <img src="<?= $ticketPreview ?>" alt="<?= __('Vista previa del boleto') ?>" class="img-fluid">
                    </div>
                <?php else: ?>
                    <div class="eventic-empty"><?= __('Configura una plantilla valida para previsualizar el pase.') ?></div>
                <?php endif; ?>
            </div>

            <div class="eventic-card">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Configuracion') ?></span>
                        <h2><?= __('Pase y marca') ?></h2>
                    </div>
                </div>
                <div class="eventic-config-list">
                    <div><span><?= __('Moneda') ?></span><strong><?= h($event->currency ?? 'MXN') ?></strong></div>
                    <div><span><?= __('Pago') ?></span><strong><?= __('Sin costo') ?></strong></div>
                    <div><span><?= __('Color primario') ?></span><strong><i style="background: <?= h($event->primary_color ?? '#1c63f2') ?>"></i><?= h($event->primary_color ?? '-') ?></strong></div>
                    <div><span><?= __('Color acento') ?></span><strong><i style="background: <?= h($event->accent_color ?? '#0ea5a4') ?>"></i><?= h($event->accent_color ?? '-') ?></strong></div>
                </div>
                <?= $this->RBAC->link(__('{0} Editar evento', $this->FontAwesome->icon('fas', 'pen')), ['action' => 'edit', $event->id], ['class' => 'btn btn-secondary w-100 mt-3', 'escape' => false]) ?>
            </div>
        </div>
    </div>
</div>
