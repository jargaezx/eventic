<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Asistentes'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Asistentes'],
]);

$available = max(0, (int)$event->capacity - (int)$event->ticket_count);
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
                <h2 class="h5 mb-3"><?= __('Carga masiva') ?></h2>
                <?php
                echo $this->Form->create(null, ['type' => 'file']);
                echo $this->Form->control('file', ['type' => 'file', 'label' => __('Archivo de asistentes')]);
                echo $this->Form->button(__('{0} Cargar archivo', $this->FontAwesome->icon('fas', 'upload')), ['class' => 'btn btn-outline-primary w-100', 'escapeTitle' => false]);
                echo $this->Form->end();
                ?>
            </div>
            <div class="eventic-kpi">
                <span><?= __('Cupo disponible') ?></span>
                <strong class="eventic-kpi-value"><?= $available ?></strong>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="eventic-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0"><?= __('Datos de asistentes') ?></h2>
                    <span class="eventic-pill"><?= __('{0} pases', count($tickets)) ?></span>
                </div>
                <?php
                echo $this->Form->create();
                foreach ($tickets as $i => $ticket) {
                    echo $this->Form->hidden("tickets.{$i}.event_id", ['value' => $event->id]);
                    echo $this->Form->hidden("tickets.{$i}.user_id", ['value' => $this->request->getAttribute('identity')->id]);
                    echo '<div class="row gy-2 gx-3 align-items-end mb-3">';
                    echo '<div class="col-12 col-md-2"><span class="eventic-pill w-100">' . __('Pase {0}', $i + 1) . '</span></div>';
                    echo '<div class="col-12 col-md-5">' . $this->Form->control("tickets.{$i}.name", ['value' => $ticket[0], 'label' => __('Nombre completo'), 'required' => true]) . '</div>';
                    echo '<div class="col-12 col-md-5">' . $this->Form->control("tickets.{$i}.email", ['value' => $ticket[1], 'label' => __('Correo electronico'), 'type' => 'email', 'required' => true]) . '</div>';
                    echo '</div>';
                }
                if (!$tickets) {
                    echo '<div class="eventic-empty">' . __('Selecciona una cantidad de pases desde el mostrador o carga un archivo.') . '</div>';
                } else {
                    echo $this->Form->button(__('{0} Emitir pases', $this->FontAwesome->icon('fas', 'paper-plane')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false]);
                }
                echo $this->Form->end();
                ?>
            </div>
        </div>
    </div>
</div>
