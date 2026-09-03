<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var bool $isEdit
 */
$isEdit = $isEdit ?? false;
?>
<?= $this->Form->create($event, ['type' => 'file']) ?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="eventic-card">
            <h2 class="h5 mb-3"><?= __('Informacion del evento') ?></h2>
            <?= $this->Form->control('owner_id', ['label' => __('Responsable')]) ?>
            <?= $this->Form->control('name', ['label' => __('Nombre del evento')]) ?>
            <?= $this->Form->control('description', ['label' => __('Descripcion'), 'rows' => 5]) ?>
            <div class="row g-3">
                <div class="col-md-6"><?= $this->Form->control('event_date', ['label' => __('Fecha del evento')]) ?></div>
                <div class="col-md-6"><?= $this->Form->control('capacity', ['label' => __('Capacidad'), 'type' => 'number', 'min' => 1]) ?></div>
            </div>
            <?php if ($isEdit): ?>
                <?= $this->Form->control('active', ['label' => __('Evento activo')]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="eventic-card mb-4">
            <h2 class="h5 mb-3"><?= __('Marca visual') ?></h2>
            <?= $this->Form->control('cover', ['type' => 'file', 'label' => __('Portada')]) ?>
            <div class="row g-3">
                <div class="col-6"><?= $this->Form->control('primary_color', ['label' => __('Color primario'), 'type' => 'color', 'value' => $event->primary_color ?: '#1c63f2']) ?></div>
                <div class="col-6"><?= $this->Form->control('accent_color', ['label' => __('Color acento'), 'type' => 'color', 'value' => $event->accent_color ?: '#0ea5a4']) ?></div>
            </div>
            <?= $this->Form->control('currency', ['label' => __('Moneda'), 'value' => $event->currency ?: 'MXN', 'maxlength' => 3]) ?>
        </div>
        <div class="eventic-card">
            <h2 class="h5 mb-3"><?= __('Comunicacion') ?></h2>
            <?= $this->Form->control('email_subject', ['label' => __('Asunto del correo'), 'placeholder' => __('Tu pase para el evento')]) ?>
            <?= $this->Form->control('email_message', ['label' => __('Mensaje del correo'), 'rows' => 5]) ?>
            <?= $this->Form->control('email_footer', ['label' => __('Pie del correo'), 'rows' => 3]) ?>
            <?= $this->Form->control('ticket_configuration.ticket', ['type' => 'file', 'label' => __('Plantilla del pase')]) ?>
            <?= $this->Form->button(__('{0} Guardar evento', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false]) ?>
        </div>
    </div>
</div>
<?= $this->Form->end() ?>
