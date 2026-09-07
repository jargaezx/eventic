<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Gestion'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
]);
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Portafolio') ?></div>
            <h1 class="eventic-title"><?= __('Gestion de eventos') ?></h1>
            <p class="eventic-subtitle"><?= __('Administra eventos, staff, pases digitales y control de acceso desde una vista clara.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Nuevo evento', $this->FontAwesome->icon('fas', 'plus')), ['action' => 'add'], ['class' => 'btn btn-primary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card mb-4">
        <?php
        echo $this->Form->create(null, [
            'valueSources' => 'query',
            'class' => 'row gy-2 gx-2 align-items-end',
        ]);
        ?>
        <div class="col-12 col-lg">
            <?= $this->Form->control('q', ['label' => __('Buscar'), 'placeholder' => __('Nombre o descripcion')]) ?>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <?= $this->Form->control('active', ['label' => __('Estado'), 'empty' => __('Todos'), 'options' => [0 => __('Inactivo'), 1 => __('Activo')]]) ?>
        </div>
        <div class="col-12 col-md-auto">
            <?= $this->Form->button(__('{0} Filtrar', $this->FontAwesome->icon('fas', 'search')), ['class' => 'btn btn-outline-primary w-100', 'escapeTitle' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <?php if (!$events->isEmpty()): ?>
        <div class="eventic-event-grid">
            <?php foreach ($events as $event): ?>
                <div>
                    <?= $this->element('event_card', compact('event')) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="eventic-empty"><?= __('No se encontraron eventos con esos filtros.') ?></div>
    <?php endif; ?>
</div>
