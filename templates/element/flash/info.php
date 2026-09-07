<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message info eventic-alert" role="status">
    <span><?= $message ?></span>
    <button type="button" aria-label="<?= __('Cerrar mensaje') ?>" onclick="this.parentElement.classList.add('hidden');">&times;</button>
</div>
