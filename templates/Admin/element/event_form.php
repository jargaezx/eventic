<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var bool $isEdit
 */
$isEdit = $isEdit ?? false;
$ticketTypes = $event->ticket_types ?? [];
if (!$ticketTypes) {
    $ticketTypes = [[
        'name' => __('Entrada general'),
        'description' => __('Acceso general al evento.'),
        'price' => '0.00',
        'capacity' => '',
        'active' => true,
    ]];
}
?>
<?= $this->Form->create($event, ['type' => 'file', 'class' => 'eventic-form eventic-event-form', 'id' => 'event-form']) ?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="eventic-card">
            <div class="eventic-card-heading">
                <div>
                    <span class="eventic-eyebrow"><?= __('Contenido') ?></span>
                    <h2><?= __('Informacion del evento') ?></h2>
                    <p><?= __('Datos visibles para administracion, reportes y registro de asistentes.') ?></p>
                </div>
            </div>
            <?php if ($isEdit): ?>
                <?= $this->Form->control('owner_id', ['label' => __('Responsable')]) ?>
            <?php endif; ?>
            <?= $this->Form->control('name', ['label' => __('Nombre del evento'), 'required' => true]) ?>
            <?= $this->Form->control('description', ['label' => __('Descripcion'), 'rows' => 5, 'placeholder' => __('Describe la experiencia, sede o informacion clave para el asistente.')]) ?>
            <div class="row g-3">
                <div class="col-md-6"><?= $this->Form->control('event_date', ['label' => __('Fecha del evento'), 'required' => true]) ?></div>
                <div class="col-md-6"><?= $this->Form->control('capacity', ['label' => __('Capacidad'), 'type' => 'number', 'min' => 1, 'required' => true]) ?></div>
            </div>
            <?php if ($isEdit): ?>
                <?= $this->Form->control('active', ['label' => __('Evento activo')]) ?>
            <?php endif; ?>
        </div>

        <div class="eventic-card mt-4">
            <div class="eventic-card-heading">
                <div>
                    <span class="eventic-eyebrow"><?= __('Venta') ?></span>
                    <h2><?= __('Tipos de boleto') ?></h2>
                    <p><?= __('Define opciones listas para vender. Cada tipo tiene precio y cupo propio opcional.') ?></p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-ticket-type>
                    <?= $this->FontAwesome->icon('fas', 'plus') ?>
                    <?= __('Agregar tipo') ?>
                </button>
            </div>

            <div class="eventic-ticket-catalog" data-ticket-catalog>
                <?php foreach (array_values((array)$ticketTypes) as $typeIndex => $type): ?>
                    <?php
                    $typeId = is_array($type) ? ($type['id'] ?? null) : $type->id;
                    $typeName = is_array($type) ? ($type['name'] ?? '') : $type->name;
                    $typeDescription = is_array($type) ? ($type['description'] ?? '') : $type->description;
                    $typePrice = is_array($type) ? ($type['price'] ?? '0.00') : $type->price;
                    $typeCapacity = is_array($type) ? ($type['capacity'] ?? '') : $type->capacity;
                    $typeActive = is_array($type) ? ($type['active'] ?? true) : $type->active;
                    ?>
                    <div class="eventic-ticket-type-card" data-ticket-type>
                        <?= $this->Form->hidden("ticket_types.{$typeIndex}.id", ['value' => $typeId]) ?>
                        <div class="eventic-ticket-type-head">
                            <div class="eventic-ticket-type-number"><?= $typeIndex + 1 ?></div>
                            <div class="row g-3 flex-fill">
                                <div class="col-12 col-lg-4">
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.name", [
                                        'label' => __('Tipo de boleto'),
                                        'value' => $typeName,
                                        'placeholder' => __('General, descuento, VIP'),
                                        'required' => true,
                                    ]) ?>
                                </div>
                                <div class="col-12 col-lg-2">
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.price", [
                                        'label' => __('Precio'),
                                        'value' => $typePrice,
                                        'type' => 'number',
                                        'min' => 0,
                                        'step' => '0.01',
                                    ]) ?>
                                </div>
                                <div class="col-12 col-lg-2">
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.capacity", [
                                        'label' => __('Cupo'),
                                        'value' => $typeCapacity,
                                        'type' => 'number',
                                        'min' => 0,
                                        'placeholder' => __('Sin limite'),
                                    ]) ?>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.description", [
                                        'label' => __('Descripcion interna'),
                                        'value' => $typeDescription,
                                        'placeholder' => __('Notas breves para administracion'),
                                    ]) ?>
                                </div>
                            </div>
                            <div class="eventic-switch-wrap">
                                <?= $this->Form->control("ticket_types.{$typeIndex}.active", [
                                    'type' => 'checkbox',
                                    'label' => __('Activo'),
                                    'checked' => (bool)$typeActive,
                                ]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="eventic-card mb-4">
            <div class="eventic-card-heading">
                <div>
                    <span class="eventic-eyebrow"><?= __('Identidad') ?></span>
                    <h2><?= __('Marca visual') ?></h2>
                    <p><?= __('Define portada, colores y moneda base del evento.') ?></p>
                </div>
            </div>
            <?= $this->Form->control('cover', ['type' => 'file', 'label' => __('Portada'), 'help' => __('Recomendada en formato horizontal para tarjetas y detalle.')]) ?>
            <div class="row g-3">
                <div class="col-6"><?= $this->Form->control('primary_color', ['label' => __('Color primario'), 'type' => 'color', 'value' => $event->primary_color ?: '#1c63f2']) ?></div>
                <div class="col-6"><?= $this->Form->control('accent_color', ['label' => __('Color acento'), 'type' => 'color', 'value' => $event->accent_color ?: '#0ea5a4']) ?></div>
            </div>
            <?= $this->Form->control('currency', ['label' => __('Moneda'), 'value' => $event->currency ?: 'MXN', 'maxlength' => 3]) ?>
        </div>
        <div class="eventic-card">
            <div class="eventic-card-heading">
                <div>
                    <span class="eventic-eyebrow"><?= __('Asistente') ?></span>
                    <h2><?= __('Comunicacion') ?></h2>
                    <p><?= __('Contenido que acompana el pase digital enviado por correo.') ?></p>
                </div>
            </div>
            <?= $this->Form->control('email_subject', ['label' => __('Asunto del correo'), 'placeholder' => __('Tu pase para el evento')]) ?>
            <?= $this->Form->control('email_message', ['label' => __('Mensaje del correo'), 'rows' => 5, 'placeholder' => __('Mensaje principal que recibira el asistente junto con su pase.')]) ?>
            <?= $this->Form->control('email_footer', ['label' => __('Pie del correo'), 'rows' => 3]) ?>
            <?= $this->Form->control('ticket_configuration.ticket', ['type' => 'file', 'label' => __('Plantilla del pase'), 'help' => __('Puedes agregarla ahora o configurarla despues desde el detalle del evento.')]) ?>
            <?= $this->Form->button(__('{0} Guardar evento', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false]) ?>
        </div>
    </div>
</div>
<?= $this->Form->end() ?>

<template id="ticket-type-template">
    <div class="eventic-ticket-type-card" data-ticket-type>
        <div class="eventic-ticket-type-head">
            <div class="eventic-ticket-type-number">1</div>
            <div class="row g-3 flex-fill">
                <div class="col-12 col-lg-4">
                    <div class="input text required">
                        <label>Tipo de boleto</label>
                        <input type="text" name="ticket_types[__TYPE__][name]" required placeholder="General, descuento, VIP">
                    </div>
                </div>
                <div class="col-12 col-lg-2">
                    <div class="input number">
                        <label>Precio</label>
                        <input type="number" min="0" step="0.01" name="ticket_types[__TYPE__][price]" value="0.00">
                    </div>
                </div>
                <div class="col-12 col-lg-2">
                    <div class="input number">
                        <label>Cupo</label>
                        <input type="number" min="0" name="ticket_types[__TYPE__][capacity]" placeholder="Sin limite">
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="input text">
                        <label>Descripcion interna</label>
                        <input type="text" name="ticket_types[__TYPE__][description]" placeholder="Notas breves para administracion">
                    </div>
                </div>
            </div>
            <div class="eventic-switch-wrap">
                <input type="hidden" name="ticket_types[__TYPE__][active]" value="0">
                <label><input type="checkbox" name="ticket_types[__TYPE__][active]" value="1" checked> Activo</label>
            </div>
        </div>
    </div>
</template>

<?php $this->Html->scriptStart(['block' => true]); ?>
(function () {
    const catalog = document.querySelector('[data-ticket-catalog]');
    const addType = document.querySelector('[data-add-ticket-type]');
    const typeTemplate = document.getElementById('ticket-type-template');
    if (!catalog || !addType || !typeTemplate) {
        return;
    }

    function typeRows() {
        return Array.from(catalog.querySelectorAll('[data-ticket-type]'));
    }

    function reindexTypes() {
        typeRows().forEach(function (type, typeIndex) {
            type.querySelector('.eventic-ticket-type-number').textContent = String(typeIndex + 1);
            Array.from(type.querySelectorAll('input, textarea, select')).forEach(function (field) {
                if (field.name) {
                    field.name = field.name.replace(/ticket_types\[\d+\]/, 'ticket_types[' + typeIndex + ']');
                    field.name = field.name.replace(/__TYPE__/g, String(typeIndex));
                }
            });
        });
    }

    addType.addEventListener('click', function () {
        const typeIndex = typeRows().length;
        catalog.insertAdjacentHTML('beforeend', typeTemplate.innerHTML.replace(/__TYPE__/g, String(typeIndex)));
        reindexTypes();
        catalog.lastElementChild.querySelector('input:not([type="hidden"])')?.focus();
    });
})();
<?php $this->Html->scriptEnd(); ?>
