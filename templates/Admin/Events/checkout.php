<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Asistentes'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Asistentes'],
]);

$available = max(0, (int)$event->capacity - (int)$event->ticket_count);
$paymentStatuses = $paymentStatuses ?? [
    'free' => __('Gratis'),
    'pending' => __('Pendiente'),
    'paid' => __('Pagado'),
];
$batchTotal = $batchTotal ?? 0;
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Emision de pases') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Captura uno o varios pases con el mismo correo de entrega, o importa una lista preparada en Excel.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Volver al registro', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'register', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="eventic-card mb-4">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Importar') ?></span>
                        <h2><?= __('Carga masiva') ?></h2>
                        <p><?= __('Archivo Excel con columnas: nombre, correo_entrega, tipo_boleto y estado_pago.') ?></p>
                    </div>
                </div>
                <div class="eventic-template-actions">
                    <?= $this->RBAC->link(__('{0} Descargar formato', $this->FontAwesome->icon('fas', 'file-excel')), ['action' => 'downloadBulkTemplate', $event->id], ['class' => 'btn btn-outline-secondary w-100', 'escape' => false]) ?>
                </div>
                <?php
                echo $this->Form->create(null, ['type' => 'file', 'class' => 'eventic-event-form']);
                echo $this->Form->control('file', ['type' => 'file', 'label' => __('Archivo de asistentes')]);
                echo $this->Form->button(__('{0} Cargar archivo', $this->FontAwesome->icon('fas', 'upload')), ['class' => 'btn btn-outline-primary w-100', 'escapeTitle' => false]);
                echo $this->Form->end();
                ?>
            </div>
            <div class="eventic-card eventic-checkout-summary">
                <div>
                    <span><?= __('Disponibles') ?></span>
                    <strong><?= $available ?></strong>
                </div>
                <div>
                    <span><?= __('A emitir') ?></span>
                    <strong><?= count($tickets) ?></strong>
                </div>
                <div>
                    <span><?= __('Importe') ?></span>
                    <strong><?= $this->Number->currency($batchTotal, $event->currency ?: 'MXN') ?></strong>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="eventic-card">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Venta') ?></span>
                        <h2><?= __('Datos de asistentes') ?></h2>
                        <p><?= __('Usa el correo de entrega principal para enviar varios pases a una sola persona sin capturar el dato repetidamente.') ?></p>
                    </div>
                    <span class="eventic-pill" data-checkout-count><?= __('{0} pases', count($tickets)) ?></span>
                </div>
                <?php
                echo $this->Form->create(null, ['class' => 'eventic-event-form', 'data-checkout-form' => true]);
                ?>
                <div class="eventic-buyer-strip">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Contacto') ?></span>
                        <strong><?= __('Correo de entrega principal') ?></strong>
                        <p><?= __('Opcional. Se aplicara a los pases que no tengan correo propio.') ?></p>
                    </div>
                    <div>
                        <?= $this->Form->control('buyer_email', ['label' => __('Correo de entrega'), 'type' => 'email', 'placeholder' => __('comprador@empresa.com'), 'data-buyer-email' => true]) ?>
                    </div>
                </div>
                <div data-checkout-rows>
                <?php
                foreach ($tickets as $i => $ticket) {
                    echo $this->Form->hidden("tickets.{$i}.event_id", ['value' => $event->id]);
                    echo $this->Form->hidden("tickets.{$i}.user_id", ['value' => $this->request->getAttribute('identity')->id]);
                    echo '<div class="eventic-checkout-row" data-checkout-row>';
                    echo '<div class="eventic-checkout-index"><span>' . __('Pase') . '</span><strong>' . ($i + 1) . '</strong></div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.name", ['value' => $ticket['name'] ?? '', 'label' => __('Nombre completo'), 'required' => true]) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.email", ['value' => $ticket['email'] ?? '', 'label' => __('Correo de entrega'), 'type' => 'email', 'required' => true, 'data-ticket-email' => true]) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.ticket_type_id", ['value' => $ticket['ticket_type_id'] ?? array_key_first($typeOptions), 'label' => __('Tipo de boleto'), 'type' => 'select', 'options' => $typeOptions, 'required' => true, 'data-ticket-type' => true]) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.payment_status", ['value' => $ticket['payment_status'] ?? 'free', 'label' => __('Pago'), 'type' => 'select', 'options' => $paymentStatuses]) . '</div>';
                    echo '<button type="button" class="eventic-icon-button eventic-remove-ticket" data-remove-ticket aria-label="' . h(__('Eliminar pase')) . '">' . $this->FontAwesome->icon('fas', 'trash-alt') . '</button>';
                    echo '</div>';
                }
                ?>
                </div>
                <div class="eventic-checkout-toolbar">
                    <button type="button" class="btn btn-outline-primary" data-add-ticket>
                        <?= $this->FontAwesome->icon('fas', 'plus') ?>
                        <?= __('Agregar pase') ?>
                    </button>
                    <span><?= __('Disponibles: {0}', $available) ?></span>
                </div>
                <?php
                echo '<div class="eventic-checkout-submit">';
                echo '<div><span>' . __('Total a emitir') . '</span><strong data-checkout-total>' . count($tickets) . ' / ' . $available . '</strong></div>';
                echo '<div><span>' . __('Importe') . '</span><strong data-checkout-amount>' . $this->Number->currency($batchTotal, $event->currency ?: 'MXN') . '</strong></div>';
                echo '<div class="eventic-cash-helper">';
                echo $this->Form->control('cash_received', ['label' => __('Recibido'), 'type' => 'number', 'min' => 0, 'step' => '0.01', 'data-cash-received' => true]);
                echo '<div><span>' . __('Cambio') . '</span><strong data-cash-change>' . $this->Number->currency(0, $event->currency ?: 'MXN') . '</strong></div>';
                echo '</div>';
                echo $this->Form->button(__('{0} Emitir pases', $this->FontAwesome->icon('fas', 'paper-plane')), ['class' => 'btn btn-primary', 'escapeTitle' => false, 'disabled' => $available === 0]);
                echo '</div>';
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
(function () {
    const form = document.querySelector('[data-checkout-form]');
    if (!form) {
        return;
    }

    const rowsContainer = form.querySelector('[data-checkout-rows]');
    const addButton = form.querySelector('[data-add-ticket]');
    const buyerEmail = form.querySelector('[data-buyer-email]');
    const countLabel = document.querySelector('[data-checkout-count]');
    const totalLabel = form.querySelector('[data-checkout-total]');
    const amountLabel = form.querySelector('[data-checkout-amount]');
    const cashReceived = form.querySelector('[data-cash-received]');
    const cashChange = form.querySelector('[data-cash-change]');
    const available = <?= (int)$available ?>;
    const currency = <?= json_encode($event->currency ?: 'MXN') ?>;
    const formatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: currency });
    const typeMeta = <?= json_encode($typeMeta) ?>;
    let currentTotal = 0;

    function rows() {
        return Array.from(rowsContainer.querySelectorAll('[data-checkout-row]'));
    }

    function reindex() {
        rows().forEach(function (row, index) {
            row.querySelector('.eventic-checkout-index strong').textContent = String(index + 1);
            row.querySelectorAll('input, select').forEach(function (field) {
                if (field.name) {
                    field.name = field.name.replace(/tickets\[\d+\]/, 'tickets[' + index + ']');
                }
                if (field.id) {
                    field.id = field.id.replace(/tickets-\d+-/, 'tickets-' + index + '-');
                }
            });
            row.querySelectorAll('label').forEach(function (label) {
                const target = label.getAttribute('for');
                if (target) {
                    label.setAttribute('for', target.replace(/tickets-\d+-/, 'tickets-' + index + '-'));
                }
            });
        });
    }

    function updateSummary() {
        const currentRows = rows();
        const total = currentRows.reduce(function (sum, row) {
            const typeId = row.querySelector('[data-ticket-type]')?.value || '';
            const price = parseFloat(typeMeta[typeId]?.price || '0');
            return sum + (Number.isFinite(price) ? price : 0);
        }, 0);
        currentTotal = total;
        if (countLabel) {
            countLabel.textContent = currentRows.length + ' pases';
        }
        if (totalLabel) {
            totalLabel.textContent = currentRows.length + ' / ' + available;
        }
        if (amountLabel) {
            amountLabel.textContent = formatter.format(total);
        }
        if (addButton) {
            addButton.disabled = currentRows.length >= available;
        }
        currentRows.forEach(function (row) {
            const remove = row.querySelector('[data-remove-ticket]');
            if (remove) {
                remove.disabled = currentRows.length === 1;
            }
            const typeId = row.querySelector('[data-ticket-type]')?.value || '';
            const payment = row.querySelector('select[name$="[payment_status]"]');
            if (payment && typeMeta[typeId]?.isFree) {
                payment.value = 'free';
            } else if (payment && payment.value === 'free') {
                payment.value = 'paid';
            }
        });
        updateCashChange();
    }

    function updateCashChange() {
        if (!cashChange) {
            return;
        }
        const received = parseFloat(cashReceived?.value || '0');
        const change = Math.max(0, (Number.isFinite(received) ? received : 0) - currentTotal);
        cashChange.textContent = formatter.format(change);
    }

    function applyBuyerEmailToEmptyRows() {
        const value = buyerEmail ? buyerEmail.value.trim().toLowerCase() : '';
        if (!value) {
            return;
        }
        rows().forEach(function (row) {
            const email = row.querySelector('[data-ticket-email]');
            if (email && !email.value.trim()) {
                email.value = value;
            }
        });
    }

    if (addButton) {
        addButton.addEventListener('click', function () {
            const currentRows = rows();
            if (!currentRows.length || currentRows.length >= available) {
                return;
            }
            const clone = currentRows[currentRows.length - 1].cloneNode(true);
            clone.querySelectorAll('input').forEach(function (field) {
                if (field.type === 'hidden') {
                    return;
                }
                field.value = '';
            });
            clone.querySelectorAll('select').forEach(function (field) {
                field.selectedIndex = 0;
            });
            rowsContainer.appendChild(clone);
            reindex();
            applyBuyerEmailToEmptyRows();
            updateSummary();
            clone.querySelector('input:not([type="hidden"])')?.focus();
        });
    }

    rowsContainer.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-ticket]');
        if (!button || rows().length === 1) {
            return;
        }
        button.closest('[data-checkout-row]').remove();
        reindex();
        updateSummary();
    });

    rowsContainer.addEventListener('input', function (event) {
        if (event.target.matches('[data-ticket-type]')) {
            updateSummary();
        }
    });

    buyerEmail?.addEventListener('input', applyBuyerEmailToEmptyRows);
    buyerEmail?.addEventListener('blur', applyBuyerEmailToEmptyRows);
    cashReceived?.addEventListener('input', updateCashChange);
    form.addEventListener('submit', applyBuyerEmailToEmptyRows);
    updateSummary();
})();
<?php $this->Html->scriptEnd(); ?>
