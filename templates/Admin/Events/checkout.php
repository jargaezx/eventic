<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Asistentes'));
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
            <p class="eventic-subtitle"><?= __('Captura asistentes manualmente o carga un archivo con nombre y correo.') ?></p>
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
                        <p><?= __('Archivo Excel con columnas: nombre, correo, precio y estado de pago.') ?></p>
                    </div>
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
                        <p><?= __('Revisa cada pase antes de emitirlo. Los correos duplicados activos se bloquean automaticamente.') ?></p>
                    </div>
                    <span class="eventic-pill"><?= __('{0} pases', count($tickets)) ?></span>
                </div>
                <?php
                echo $this->Form->create(null, ['class' => 'eventic-event-form']);
                foreach ($tickets as $i => $ticket) {
                    echo $this->Form->hidden("tickets.{$i}.event_id", ['value' => $event->id]);
                    echo $this->Form->hidden("tickets.{$i}.user_id", ['value' => $this->request->getAttribute('identity')->id]);
                    echo '<div class="eventic-checkout-row">';
                    echo '<div class="eventic-checkout-index"><span>' . __('Pase') . '</span><strong>' . ($i + 1) . '</strong></div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.name", ['value' => $ticket['name'] ?? '', 'label' => __('Nombre completo'), 'required' => true]) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.email", ['value' => $ticket['email'] ?? '', 'label' => __('Correo electronico'), 'type' => 'email', 'required' => true]) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.price", ['value' => $ticket['price'] ?? '0.00', 'label' => __('Precio'), 'type' => 'number', 'min' => 0, 'step' => '0.01']) . '</div>';
                    echo '<div>' . $this->Form->control("tickets.{$i}.payment_status", ['value' => $ticket['payment_status'] ?? 'free', 'label' => __('Pago'), 'type' => 'select', 'options' => $paymentStatuses]) . '</div>';
                    echo '</div>';
                }
                if (!$tickets) {
                    echo '<div class="eventic-empty"><strong>' . __('Sin pases por capturar') . '</strong><span>' . __('Selecciona una cantidad desde el mostrador o carga un archivo.') . '</span></div>';
                } else {
                    echo '<div class="eventic-checkout-submit">';
                    echo '<div><span>' . __('Total a emitir') . '</span><strong>' . count($tickets) . ' / ' . $available . '</strong></div>';
                    echo $this->Form->button(__('{0} Emitir pases', $this->FontAwesome->icon('fas', 'paper-plane')), ['class' => 'btn btn-primary', 'escapeTitle' => false]);
                    echo '</div>';
                }
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </div>
</div>
