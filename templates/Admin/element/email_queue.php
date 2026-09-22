<?php
$queueMetrics = [
    ['key' => 'pending', 'label' => __('Pendientes'), 'description' => __('En espera o en proceso')],
    ['key' => 'processed', 'label' => __('Procesados'), 'description' => __('Enviados al servidor de correo')],
    ['key' => 'failed', 'label' => __('Fallidos'), 'description' => __('Agotaron sus intentos de envío')],
    ['key' => 'overdue', 'label' => __('Atrasados'), 'description' => __('Incluidos en pendientes')],
];
?>
<section class="eventic-card mb-4" id="email-queue" aria-labelledby="email-queue-heading">
    <div class="eventic-card-heading flex-wrap gap-3">
        <div>
            <span class="eventic-eyebrow"><?= __('Operación') ?></span>
            <h2 id="email-queue-heading"><?= __('Cola de correos') ?></h2>
        </div>
        <?= $this->Html->link(__('Actualizar estado'), ['action' => 'view', $event->id, '#' => 'email-queue'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
    <dl class="row g-3 mb-3">
        <?php foreach ($queueMetrics as $metric): ?>
            <div class="col-6 col-xl-3">
                <div class="eventic-kpi h-100">
                    <div class="eventic-kpi-copy">
                        <dt><?= h($metric['label']) ?></dt>
                        <dd class="eventic-kpi-value mb-0" data-email-queue-count="<?= h($metric['key']) ?>"><?= $this->Number->format($emailQueue[$metric['key']]) ?></dd>
                        <span><?= h($metric['description']) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php if ($emailQueue['total'] === 0): ?>
        <p><?= __('Este evento todavía no tiene correos en cola.') ?></p>
    <?php endif; ?>
    <?php if ($emailQueue['uncertain'] > 0): ?>
        <div class="alert alert-warning" role="status">
            <strong><?= __('{0} entregas por revisar', $emailQueue['uncertain']) ?></strong>
            <p class="mb-0"><?= __('El envío se interrumpió y pudo haber llegado. Estos correos no se reenvían automáticamente: verifica la entrega con el proveedor de correo o con el destinatario.') ?></p>
        </div>
        <?php foreach ($uncertainEmails as $uncertainEmail): ?>
            <div class="border rounded p-3 mb-3">
                <strong class="text-break"><?= h($uncertainEmail->recipient_email) ?></strong>
                <p class="small text-muted"><?= __('Entrega por confirmar · {0}', h($uncertainEmail->modified?->i18nFormat('dd/MM/yyyy HH:mm'))) ?></p>
                <?= $this->Form->create(null, ['url' => ['action' => 'resolveEmailDelivery', $event->id, $uncertainEmail->id]]) ?>
                <?= $this->Form->control('decision', [
                    'type' => 'select', 'label' => __('Resultado de la revisión'),
                    'options' => ['sent' => __('Confirmé que sí se entregó'), 'retry' => __('Confirmé que no se entregó: reintentar')],
                    'empty' => __('Selecciona un resultado'), 'required' => true,
                    'id' => 'decision-' . $uncertainEmail->id,
                ]) ?>
                <?= $this->Form->control('verified', [
                    'type' => 'checkbox', 'value' => '1', 'required' => true,
                    'label' => __('Ya verifiqué la entrega con el proveedor o destinatario.'),
                    'id' => 'verified-' . $uncertainEmail->id,
                ]) ?>
                <?= $this->Form->button(__('Resolver entrega'), ['class' => 'btn btn-outline-primary']) ?>
                <?= $this->Form->end() ?>
            </div>
        <?php endforeach; ?>
        <?php if ($canRetryEmailJobs && $emailQueue['uncertain'] > 20): ?>
            <p class="small"><?= __('Se muestran las primeras 20 entregas. Al resolverlas aparecerán las siguientes.') ?></p>
        <?php endif; ?>
    <?php endif; ?>
    <p class="small text-muted mb-2">
        <?= __('En proceso: {0}. Cancelados: {1}. El estado se actualiza al cargar esta vista.', $emailQueue['processing'], $emailQueue['cancelled']) ?>
        <?= __('Un correo se considera atrasado después de {0} minutos desde su hora programada o desde que comenzó a procesarse.', \App\Model\Table\EmailJobsTable::OVERDUE_AFTER_MINUTES) ?>
    </p>
    <?php if ($emailQueue['overdue'] > 0): ?>
        <p class="alert alert-warning" role="status"><?= __('Hay correos atrasados. Revisa que el procesador de correos esté activo. Los envíos en proceso no se reintentan desde aquí para evitar duplicados.') ?></p>
    <?php endif; ?>
    <?php if ($canRetryEmailJobs && $emailQueue['failed'] > 0): ?>
        <?= $this->Form->create(null, [
            'url' => ['action' => 'retryFailedEmails', $event->id],
            'id' => 'retry-failed-emails',
        ]) ?>
        <p class="small" id="retry-failed-emails-help"><?= __('Se reintentarán solo los fallidos con un pase activo que no tengan otro envío pendiente ni un envío posterior procesado. Se conservarán el destinatario y el límite de intentos.') ?></p>
        <?= $this->Form->button(__('Reintentar fallidos'), [
            'class' => 'btn btn-outline-primary',
            'aria-describedby' => 'retry-failed-emails-help',
        ]) ?>
        <?= $this->Form->end() ?>
        <?php $this->Html->scriptStart(['block' => true]); ?>
        document.getElementById('retry-failed-emails').addEventListener('submit', function (event) {
            if (this.dataset.submitting) {
                event.preventDefault();
                return;
            }
            this.dataset.submitting = '1';
            this.setAttribute('aria-busy', 'true');
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = <?= json_encode(__('Reintentando…'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        });
        <?php $this->Html->scriptEnd(); ?>
    <?php endif; ?>
</section>
