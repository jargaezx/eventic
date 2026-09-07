<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Editar'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Editar'],
]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Configuracion') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Actualiza informacion, marca visual y comunicacion del evento.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
            <?= $this->Form->button(__('{0} Guardar', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary', 'form' => 'event-form', 'escapeTitle' => false]) ?>
        </div>
    </div>
    <?= $this->element('event_form', ['event' => $event, 'isEdit' => true]) ?>
</div>
