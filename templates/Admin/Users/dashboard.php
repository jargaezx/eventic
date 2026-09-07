<?php
$this->assign('title', __('Panel'));
$this->assign('subtitle', __('Operacion'));
$this->assign('eventicPage', '1');

$percent = fn ($value) => $this->Number->toPercentage((float)$value, 1);
$kpis = [
    ['icon' => 'calendar-check', 'label' => __('Eventos'), 'value' => $dashboard['events']],
    ['icon' => 'ticket-alt', 'label' => __('Registros'), 'value' => $dashboard['tickets']],
    ['icon' => 'chart-pie', 'label' => __('Ocupacion'), 'value' => $percent($dashboard['occupancy'])],
    ['icon' => 'user-check', 'label' => __('Check-in'), 'value' => $percent($dashboard['checkin'])],
];
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Centro de control') ?></div>
            <h1 class="eventic-title"><?= __('Operacion de eventos') ?></h1>
            <p class="eventic-subtitle"><?= __('Monitorea registros, capacidad y accesos para cada evento activo.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Nuevo evento', $this->FontAwesome->icon('fas', 'plus')), ['controller' => 'Events', 'action' => 'add'], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Ver eventos', $this->FontAwesome->icon('fas', 'calendar-alt')), ['controller' => 'Events', 'action' => 'index'], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
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
        <div class="col-12 col-xl-8">
            <div class="eventic-section-header">
                <h2><?= __('Eventos en operacion') ?></h2>
                <span class="eventic-pill"><?= __('Disponibles: {0}', $dashboard['available']) ?></span>
            </div>
            <?php if (!$myEvents->isEmpty()): ?>
                <div class="eventic-event-grid eventic-dashboard-grid">
                    <?php foreach ($myEvents as $event): ?>
                        <div>
                            <?= $this->element('event_card', compact('event')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="eventic-empty">
                    <strong><?= __('Sin eventos activos') ?></strong>
                    <span><?= __('Crea tu primer evento para comenzar a recibir registros.') ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-xl-4">
            <div class="eventic-card">
                <div class="eventic-section-header">
                    <h2><?= __('Actividad reciente') ?></h2>
                    <span class="eventic-pill"><?= __('Ultimos 8') ?></span>
                </div>
                <?php if ($dashboard['recentTickets']): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dashboard['recentTickets'] as $item): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between gap-3 eventic-activity-item">
                                <div>
                                    <strong><?= h($item['ticket']->name) ?></strong>
                                    <div class="text-muted small"><?= h($item['event']->name) ?></div>
                                </div>
                                <span class="text-muted small"><?= h($item['ticket']->created) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="eventic-empty">
                        <strong><?= __('Sin registros recientes') ?></strong>
                        <span><?= __('Cuando lleguen registros apareceran aqui.') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
