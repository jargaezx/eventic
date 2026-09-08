<?php
$this->assign('title', __('Panel'));
$this->assign('subtitle', __('Operacion'));
$this->assign('eventicPage', '1');

$percent = fn ($value) => $this->Number->toPercentage((float)$value, 1);
$kpis = [
    ['icon' => 'calendar-check', 'label' => __('Eventos'), 'value' => $dashboard['events'], 'hint' => __('activos y asignados')],
    ['icon' => 'ticket-alt', 'label' => __('Registros'), 'value' => $dashboard['tickets'], 'hint' => __('boletos emitidos')],
    ['icon' => 'chart-line', 'label' => __('Ocupacion'), 'value' => $percent($dashboard['occupancy']), 'hint' => __('capacidad utilizada')],
    ['icon' => 'user-check', 'label' => __('Check-in'), 'value' => $percent($dashboard['checkin']), 'hint' => __('asistencia validada')],
];
?>

<div class="eventic-shell nova-dashboard">
    <section class="nova-dashboard-hero">
        <div>
            <span class="eventic-eyebrow"><?= __('Centro de mando') ?></span>
            <h1><?= __('Panel vivo para operar eventos.') ?></h1>
            <p><?= __('Monitorea registros, capacidad, staff, ventas y accesos desde una consola moderna, directa y lista para operacion comercial.') ?></p>
        </div>
        <div class="nova-hero-actions">
            <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'plus') . ' ' . __('Nuevo evento'), ['controller' => 'Events', 'action' => 'add'], ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'calendar-days') . ' ' . __('Portafolio'), ['controller' => 'Events', 'action' => 'index'], ['class' => 'btn btn-outline-light', 'escape' => false]) ?>
        </div>
    </section>

    <section class="nova-kpi-grid" aria-label="<?= __('Indicadores principales') ?>">
        <?php foreach ($kpis as $kpi): ?>
            <article class="nova-kpi-card">
                <span class="nova-kpi-icon"><?= $this->FontAwesome->icon('fas', $kpi['icon']) ?></span>
                <div>
                    <span><?= $kpi['label'] ?></span>
                    <strong><?= $kpi['value'] ?></strong>
                    <small><?= $kpi['hint'] ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="nova-dashboard-grid">
        <section class="nova-panel nova-panel-main">
            <div class="nova-panel-heading">
                <div>
                    <span><?= __('Eventos en operacion') ?></span>
                    <h2><?= __('Portafolio activo') ?></h2>
                </div>
                <span class="nova-soft-pill"><?= __('Disponibles: {0}', $dashboard['available']) ?></span>
            </div>

            <?php if (!$myEvents->isEmpty()): ?>
                <div class="nova-compact-events">
                    <?php foreach ($myEvents as $event): ?>
                        <?php
                        $capacity = max(0, (int)$event->capacity);
                        $sold = (int)($event->ticket_count ?? 0);
                        $available = max(0, $capacity - $sold);
                        $occupancy = $capacity > 0 ? min(100, ($sold / $capacity) * 100) : 0;
                        $dir = preg_replace('#^webroot/#', '', str_replace('\\', '/', (string)$event->cover_dir));
                        $cover = $event->cover ? '/' . $dir . $event->cover : null;
                        ?>
                        <article class="nova-event-row">
                            <div class="nova-event-thumb">
                                <?php if ($cover): ?>
                                    <img src="<?= h($cover) ?>" alt="<?= h($event->name) ?>">
                                <?php else: ?>
                                    <span><?= h(mb_strtoupper(mb_substr((string)$event->name, 0, 1))) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="nova-event-row-body">
                                <div class="nova-event-row-title">
                                    <h3><?= h($event->name) ?></h3>
                                    <span class="eventic-status <?= $event->active ? 'is-active' : '' ?>"><?= $event->active ? __('Activo') : __('Inactivo') ?></span>
                                </div>
                                <p><?= h($event->description) ?></p>
                                <div class="nova-event-meta">
                                    <span><?= $this->FontAwesome->icon('fas', 'calendar-days') ?> <?= h($event->event_date) ?></span>
                                    <span><?= $this->FontAwesome->icon('fas', 'chair') ?> <?= __('{0} lugares', $this->Number->format($capacity)) ?></span>
                                    <span><?= $this->FontAwesome->icon('fas', 'ticket') ?> <?= __('{0} libres', $this->Number->format($available)) ?></span>
                                </div>
                                <div class="nova-progress-line">
                                    <span style="width: <?= h((string)$occupancy) ?>%"></span>
                                </div>
                            </div>
                            <div class="nova-event-actions">
                                <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'arrow-right') . ' ' . __('Abrir'), ['controller' => 'Events', 'action' => 'view', $event->id], ['class' => 'btn btn-primary', 'escape' => false]) ?>
                                <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'clipboard-list'), ['controller' => 'Events', 'action' => 'register', $event->id], ['class' => 'nova-icon-action', 'escape' => false, 'title' => __('Registrar')]) ?>
                                <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'qrcode'), ['controller' => 'Events', 'action' => 'scan', $event->id], ['class' => 'nova-icon-action', 'escape' => false, 'title' => __('Escanear')]) ?>
                                <?= $this->RBAC->link($this->FontAwesome->icon('fas', 'chart-simple'), ['controller' => 'Events', 'action' => 'report', $event->id], ['class' => 'nova-icon-action', 'escape' => false, 'title' => __('Reporte')]) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="eventic-empty">
                    <strong><?= __('Sin eventos activos') ?></strong>
                    <span><?= __('Crea tu primer evento para comenzar a recibir registros.') ?></span>
                </div>
            <?php endif; ?>
        </section>

        <aside class="nova-panel">
            <div class="nova-panel-heading">
                <div>
                    <span><?= __('Seguimiento') ?></span>
                    <h2><?= __('Actividad reciente') ?></h2>
                </div>
                <span class="nova-soft-pill"><?= __('Ultimos 8') ?></span>
            </div>
            <?php if ($dashboard['recentTickets']): ?>
                <div class="nova-activity-list">
                    <?php foreach ($dashboard['recentTickets'] as $item): ?>
                        <div class="nova-activity-item">
                            <span><?= $this->FontAwesome->icon('fas', 'ticket') ?></span>
                            <div>
                                <strong><?= h($item['ticket']->name) ?></strong>
                                <small><?= h($item['event']->name) ?></small>
                            </div>
                            <time><?= h($item['ticket']->created) ?></time>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="eventic-empty">
                    <strong><?= __('Sin registros recientes') ?></strong>
                    <span><?= __('Cuando lleguen registros apareceran aqui.') ?></span>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
