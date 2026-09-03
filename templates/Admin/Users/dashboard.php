<?php
$this->assign('title', __('Panel'));
$this->assign('subtitle', __('Operacion'));

$percent = fn ($value) => $this->Number->toPercentage((float)$value, 1);
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
        <div class="col-6 col-xl-3">
            <div class="eventic-kpi">
                <span><?= __('Eventos') ?></span>
                <strong class="eventic-kpi-value"><?= $dashboard['events'] ?></strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="eventic-kpi">
                <span><?= __('Registros') ?></span>
                <strong class="eventic-kpi-value"><?= $dashboard['tickets'] ?></strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="eventic-kpi">
                <span><?= __('Ocupacion') ?></span>
                <strong class="eventic-kpi-value"><?= $percent($dashboard['occupancy']) ?></strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="eventic-kpi">
                <span><?= __('Check-in') ?></span>
                <strong class="eventic-kpi-value"><?= $percent($dashboard['checkin']) ?></strong>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0"><?= __('Eventos en operacion') ?></h2>
                <span class="eventic-pill"><?= __('Disponibles: {0}', $dashboard['available']) ?></span>
            </div>
            <?php if (!$myEvents->isEmpty()): ?>
                <div class="row row-cols-1 row-cols-lg-2 g-3">
                    <?php foreach ($myEvents as $event): ?>
                        <div class="col">
                            <?= $this->element('event_card', compact('event')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="eventic-empty"><?= __('Aun no tienes eventos asignados.') ?></div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-xl-4">
            <div class="eventic-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0"><?= __('Actividad reciente') ?></h2>
                    <span class="eventic-pill"><?= __('Ultimos 8') ?></span>
                </div>
                <?php if ($dashboard['recentTickets']): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dashboard['recentTickets'] as $item): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                                <div>
                                    <strong><?= h($item['ticket']->name) ?></strong>
                                    <div class="text-muted small"><?= h($item['event']->name) ?></div>
                                </div>
                                <span class="text-muted small"><?= h($item['ticket']->created) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="eventic-empty"><?= __('Cuando lleguen registros apareceran aqui.') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
