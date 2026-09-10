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
        'capacity' => $event->capacity ?: '',
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
            <?= $this->Form->control('location', ['label' => __('Ubicacion'), 'placeholder' => __('Sede, salon, direccion o enlace de acceso')]) ?>
            <?php if ($isEdit): ?>
                <?= $this->Form->control('active', ['label' => __('Evento activo')]) ?>
            <?php endif; ?>
        </div>

        <div class="eventic-card mt-4">
            <div class="eventic-card-heading">
                <div>
                    <span class="eventic-eyebrow"><?= __('Venta') ?></span>
                    <h2><?= __('Tipos de boleto') ?></h2>
                    <p><?= __('Distribuye la capacidad total del evento entre los tipos de boleto disponibles para venta.') ?></p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-ticket-type>
                    <?= $this->FontAwesome->icon('fas', 'plus') ?>
                    <?= __('Agregar tipo') ?>
                </button>
            </div>

            <div class="eventic-ticket-capacity-meter" data-ticket-capacity-meter aria-live="polite">
                <div>
                    <span><?= __('Capacidad del evento') ?></span>
                    <strong data-event-capacity-total><?= (int)($event->capacity ?? 0) ?></strong>
                </div>
                <div>
                    <span><?= __('Boletos asignados') ?></span>
                    <strong data-ticket-capacity-assigned>0</strong>
                </div>
                <div>
                    <span data-ticket-capacity-status-label><?= __('Pendientes por asignar') ?></span>
                    <strong data-ticket-capacity-remaining>0</strong>
                </div>
                <p data-ticket-capacity-message></p>
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
                            <div class="eventic-ticket-type-fields">
                                <div>
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.name", [
                                        'label' => __('Tipo de boleto'),
                                        'value' => $typeName,
                                        'placeholder' => __('General, descuento, VIP'),
                                        'required' => true,
                                    ]) ?>
                                </div>
                                <div>
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.price", [
                                        'label' => __('Precio'),
                                        'value' => $typePrice,
                                        'type' => 'number',
                                        'min' => 0,
                                        'step' => '0.01',
                                    ]) ?>
                                </div>
                                <div>
                                    <?= $this->Form->control("ticket_types.{$typeIndex}.capacity", [
                                        'label' => __('Cantidad de boletos'),
                                        'value' => $typeCapacity,
                                        'type' => 'number',
                                        'min' => 0,
                                        'placeholder' => __('0'),
                                        'data-ticket-type-capacity' => true,
                                    ]) ?>
                                </div>
                                <div>
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
                <div class="col-6"><?= $this->Form->control('primary_color', ['label' => __('Color primario'), 'type' => 'color', 'value' => $event->primary_color ?: '#76132c']) ?></div>
                <div class="col-6"><?= $this->Form->control('accent_color', ['label' => __('Color acento'), 'type' => 'color', 'value' => $event->accent_color ?: '#c99a3f']) ?></div>
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
            <div class="eventic-ticket-type-fields">
                <div>
                    <div class="input text required">
                        <label>Tipo de boleto</label>
                        <input class="form-control" type="text" name="ticket_types[__TYPE__][name]" required placeholder="General, descuento, VIP">
                    </div>
                </div>
                <div>
                    <div class="input number">
                        <label>Precio</label>
                        <input class="form-control" type="number" min="0" step="0.01" name="ticket_types[__TYPE__][price]" value="0.00">
                    </div>
                </div>
                <div>
                    <div class="input number">
                        <label>Cantidad de boletos</label>
                        <input class="form-control" type="number" min="0" name="ticket_types[__TYPE__][capacity]" placeholder="0" data-ticket-type-capacity>
                    </div>
                </div>
                <div>
                    <div class="input textarea">
                        <label>Descripcion interna</label>
                        <textarea class="form-control" name="ticket_types[__TYPE__][description]" rows="3" placeholder="Notas breves para administracion"></textarea>
                    </div>
                </div>
            </div>
            <div class="eventic-switch-wrap">
                <input type="hidden" name="ticket_types[__TYPE__][active]" value="0">
                <label><input class="form-check-input" type="checkbox" name="ticket_types[__TYPE__][active]" value="1" checked> Activo</label>
            </div>
        </div>
    </div>
</template>

<?php $this->Html->scriptStart(['block' => true]); ?>
(function () {
    const catalog = document.querySelector('[data-ticket-catalog]');
    const addType = document.querySelector('[data-add-ticket-type]');
    const typeTemplate = document.getElementById('ticket-type-template');
    const eventCapacity = document.querySelector('[name="capacity"]');
    const meter = document.querySelector('[data-ticket-capacity-meter]');
    const form = document.getElementById('event-form');
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

    function activeTypeRows() {
        return typeRows().filter(function (type) {
            const active = type.querySelector('input[type="checkbox"][name$="[active]"]');
            return !active || active.checked;
        });
    }

    function updateCapacityMeter() {
        if (!meter) {
            return;
        }
        const total = Math.max(0, parseInt(eventCapacity?.value || '0', 10) || 0);
        let assigned = 0;
        let emptyActiveTypes = 0;
        activeTypeRows().forEach(function (type) {
            const input = type.querySelector('[data-ticket-type-capacity]');
            const quantity = Math.max(0, parseInt(input?.value || '0', 10) || 0);
            if (quantity <= 0) {
                emptyActiveTypes++;
            }
            assigned += quantity;
        });
        const remaining = total - assigned;
        meter.dataset.status = emptyActiveTypes > 0 ? 'invalid' : (remaining === 0 ? 'complete' : (remaining > 0 ? 'pending' : 'exceeded'));
        meter.querySelector('[data-event-capacity-total]').textContent = String(total);
        meter.querySelector('[data-ticket-capacity-assigned]').textContent = String(assigned);
        meter.querySelector('[data-ticket-capacity-remaining]').textContent = String(Math.abs(remaining));
        meter.querySelector('[data-ticket-capacity-status-label]').textContent = remaining < 0 ? 'Boletos excedidos' : 'Pendientes por asignar';
        const message = meter.querySelector('[data-ticket-capacity-message]');
        if (message) {
            if (emptyActiveTypes > 0) {
                message.textContent = 'Cada tipo activo debe tener al menos 1 boleto asignado.';
            } else if (remaining === 0) {
                message.textContent = 'La capacidad esta completamente distribuida.';
            } else if (remaining > 0) {
                message.textContent = 'Asigna los ' + remaining + ' boletos restantes a uno o mas tipos.';
            } else {
                message.textContent = 'Reduce ' + Math.abs(remaining) + ' boletos para igualar la capacidad del evento.';
            }
        }

        return {total, assigned, remaining};
    }

    function autoFillSingleType() {
        const activeRows = activeTypeRows();
        if (activeRows.length !== 1) {
            return;
        }
        const input = activeRows[0].querySelector('[data-ticket-type-capacity]');
        if (input && (input.value === '' || input.value === '0') && eventCapacity?.value) {
            input.value = eventCapacity.value;
        }
    }

    addType.addEventListener('click', function () {
        const typeIndex = typeRows().length;
        catalog.insertAdjacentHTML('beforeend', typeTemplate.innerHTML.replace(/__TYPE__/g, String(typeIndex)));
        reindexTypes();
        catalog.lastElementChild.querySelector('input:not([type="hidden"])')?.focus();
        updateCapacityMeter();
    });

    catalog.addEventListener('input', updateCapacityMeter);
    catalog.addEventListener('change', updateCapacityMeter);
    eventCapacity?.addEventListener('input', function () {
        autoFillSingleType();
        updateCapacityMeter();
    });

    form?.addEventListener('submit', function (event) {
        const state = updateCapacityMeter();
        if (!state || state.total <= 0 || (state.remaining === 0 && meter?.dataset.status === 'complete')) {
            return;
        }

        event.preventDefault();
        const message = meter?.dataset.status === 'invalid'
            ? 'Cada tipo activo debe tener al menos 1 boleto asignado.'
            : state.remaining > 0
            ? 'Faltan ' + state.remaining + ' boletos por asignar a un tipo.'
            : 'Hay ' + Math.abs(state.remaining) + ' boletos excedidos en los tipos.';
        meter?.scrollIntoView({behavior: 'smooth', block: 'center'});
        meter?.setAttribute('aria-live', 'polite');
        meter?.querySelector('[data-ticket-capacity-status-label]')?.setAttribute('title', message);
    });

    autoFillSingleType();
    updateCapacityMeter();
})();
<?php $this->Html->scriptEnd(); ?>
