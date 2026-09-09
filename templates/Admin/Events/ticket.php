<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Pase'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Registro', 'url' => ['action' => 'register', $event->id]],
    ['title' => 'Pase'],
]);

$folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
$ticketImage = '/files/tickets/' . $ticket->id . '.png';
$ticketImagePath = WWW_ROOT . 'files' . DS . 'tickets' . DS . $ticket->id . '.png';
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Ficha de pase') ?></div>
            <h1 class="eventic-title"><?= __('Pase {0}', h($folio)) ?></h1>
            <p class="eventic-subtitle"><?= h($event->name) ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Registro', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'register', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
            <?= $this->RBAC->link(__('{0} Escanear', $this->FontAwesome->icon('fas', 'qrcode')), ['action' => 'scan', $event->id], ['class' => 'btn btn-outline-primary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="eventic-card eventic-ticket-profile">
                <div class="eventic-ticket-profile-head">
                    <span><?= __('Folio') ?></span>
                    <strong><?= h($folio) ?></strong>
                    <?= $this->Html->badge($ticket->active ? __('Activo') : __('Cancelado'), ['class' => $ticket->active ? 'success' : 'light']) ?>
                </div>
                <?php if (is_file($ticketImagePath)): ?>
                    <img src="<?= h($ticketImage) ?>" alt="<?= __('Pase {0}', h($folio)) ?>" class="eventic-ticket-image">
                <?php else: ?>
                    <div class="eventic-empty">
                        <strong><?= __('Pase no renderizado') ?></strong>
                        <span><?= __('Se generara al reenviar el correo.') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="eventic-card mb-4">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Asistente') ?></span>
                        <h2><?= h($ticket->name) ?></h2>
                        <p><?= h($ticket->email) ?></p>
                    </div>
                </div>
                <div class="eventic-audit-grid">
                    <div><span><?= __('Emitido') ?></span><strong><?= h($ticket->created) ?></strong></div>
                    <div><span><?= __('Tipo de boleto') ?></span><strong><?= h($ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? '-')) ?></strong></div>
                    <div><span><?= __('Importe') ?></span><strong><?= $this->Number->currency((float)$ticket->price, $ticket->currency ?: ($event->currency ?: 'MXN')) ?></strong></div>
                    <div><span><?= __('Registrado por') ?></span><strong><?= h($ticket->registered_by_user->full_name ?? '-') ?></strong></div>
                    <div><span><?= __('Ultimo correo') ?></span><strong><?= $ticket->last_emailed ? h($ticket->last_emailed) : __('Sin confirmar') ?></strong></div>
                    <div><span><?= __('Intentos de envio') ?></span><strong><?= (int)$ticket->email_attempt_count ?></strong></div>
                    <div><span><?= __('Asistencia') ?></span><strong><?= $ticket->attended ? h($ticket->attended) : __('Pendiente') ?></strong></div>
                    <div><span><?= __('Escaneado por') ?></span><strong><?= h($ticket->checked_in_user->full_name ?? '-') ?></strong></div>
                    <div><span><?= __('Cancelado') ?></span><strong><?= $ticket->cancelled ? h($ticket->cancelled) : '-' ?></strong></div>
                    <div><span><?= __('Cancelado por') ?></span><strong><?= h($ticket->cancelled_by_user->full_name ?? '-') ?></strong></div>
                </div>
                <?php if ($ticket->cancelled_reason): ?>
                    <div class="eventic-note mt-3">
                        <span><?= __('Motivo de cancelacion') ?></span>
                        <strong><?= h($ticket->cancelled_reason) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="eventic-card">
                <div class="eventic-card-heading">
                    <div>
                        <span class="eventic-eyebrow"><?= __('Acciones') ?></span>
                        <h2><?= __('Gestion del pase') ?></h2>
                        <p><?= __('Actualiza el correo, reenvia el pase o cancela el acceso cuando sea necesario.') ?></p>
                    </div>
                </div>
                <?php if ($ticket->active): ?>
                    <?= $this->Form->create(null, [
                        'url' => ['action' => 'resendTicket', $event->id, $ticket->id],
                        'class' => 'eventic-ticket-detail-actions',
                    ]) ?>
                    <?= $this->Form->control('email', [
                        'label' => __('Correo del asistente'),
                        'type' => 'email',
                        'value' => $ticket->email,
                    ]) ?>
                    <?= $this->Form->button(__('{0} Reenviar pase', $this->FontAwesome->icon('fas', 'paper-plane')), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
                    <?= $this->Form->end() ?>

                    <?= $this->Form->create(null, [
                        'url' => ['action' => 'cancelTicket', $event->id, $ticket->id],
                        'class' => 'eventic-ticket-detail-actions mt-3',
                        'onsubmit' => 'return confirm("' . h(__('Este pase quedara cancelado y no podra utilizarse en el acceso.')) . '");',
                    ]) ?>
                    <?= $this->Form->control('cancelled_reason', [
                        'label' => __('Motivo de cancelacion'),
                        'placeholder' => __('Ej. correo duplicado, solicitud del asistente o registro incorrecto'),
                    ]) ?>
                    <?= $this->Form->button(__('{0} Cancelar pase', $this->FontAwesome->icon('fas', 'ban')), [
                        'class' => 'btn btn-outline-danger',
                        'escapeTitle' => false,
                    ]) ?>
                    <?= $this->Form->end() ?>
                <?php else: ?>
                    <div class="eventic-empty">
                        <strong><?= __('Pase cancelado') ?></strong>
                        <span><?= __('Este pase se conserva solo para auditoria y reportes.') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
