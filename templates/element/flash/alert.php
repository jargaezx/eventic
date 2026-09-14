<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $type
 */
$type = $type ?? 'info';
$icons = [
    'success' => 'check-circle',
    'error' => 'circle-exclamation',
    'warning' => 'triangle-exclamation',
    'info' => 'circle-info',
];
$titles = [
    'success' => __('Listo'),
    'error' => __('Revisa la información'),
    'warning' => __('Atención'),
    'info' => __('Información'),
];
?>
<div class="message <?= h($type) ?> eventic-alert" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
    <span class="eventic-alert-icon" aria-hidden="true">
        <?= $this->FontAwesome->icon('fas', $icons[$type] ?? $icons['info']) ?>
    </span>
    <span class="eventic-alert-copy">
        <strong><?= $titles[$type] ?? $titles['info'] ?></strong>
        <span><?= $message ?></span>
    </span>
    <button type="button" class="eventic-alert-close" aria-label="<?= __('Cerrar mensaje') ?>" onclick="this.closest('.eventic-alert').classList.add('hidden');">
        <?= $this->FontAwesome->icon('fas', 'xmark') ?>
    </button>
</div>
