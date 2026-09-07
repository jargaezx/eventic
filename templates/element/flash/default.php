<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
$class = 'message';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="<?= h($class) ?> eventic-alert" role="status">
    <span><?= $message ?></span>
    <button type="button" aria-label="<?= __('Cerrar mensaje') ?>" onclick="this.parentElement.classList.add('hidden');">&times;</button>
</div>
