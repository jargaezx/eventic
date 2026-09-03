<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Nuevo'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Nuevo'],
]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Configuracion') ?></div>
            <h1 class="eventic-title"><?= __('Nuevo evento') ?></h1>
            <p class="eventic-subtitle"><?= __('Define la informacion principal, marca visual y comunicacion del evento.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Volver', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'index'], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>
    <?= $this->element('event_form', ['event' => $event, 'isEdit' => false]) ?>
</div>
