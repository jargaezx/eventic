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
$type = 'info';
if (str_contains($class, 'success')) {
    $type = 'success';
} elseif (str_contains($class, 'error') || str_contains($class, 'danger')) {
    $type = 'error';
} elseif (str_contains($class, 'warning')) {
    $type = 'warning';
}
?>
<?= $this->element('flash/alert', ['message' => $message, 'type' => $type]) ?>
