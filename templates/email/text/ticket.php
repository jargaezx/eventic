<?= __('Pase digital: {0}', $event->name) ?>

<?= __('Hola {0},', $ticket->name) ?>

<?= trim((string)($event->email_message ?: __('Tu pase digital esta listo. Presenta el codigo QR adjunto al llegar al acceso.'))) ?>

<?= __('Folio: {0}', str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?>
<?= __('Fecha: {0}', $event->event_date) ?>
<?= __('Acceso: Validacion con QR') ?>

<?= trim((string)($event->email_footer ?: __('Conserva este correo y evita compartir tu pase.'))) ?>
