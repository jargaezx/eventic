<?= __('Pase digital confirmado: {0}', $event->name) ?>

<?= __('Hola {0},', $ticket->name) ?>

<?= trim((string)($event->email_message ?: __('Tu pase digital esta listo. Presenta el codigo QR adjunto al llegar al acceso.'))) ?>

<?= __('Folio: {0}', $ticket->get('is_test') ? __('PRUEBA') : str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?>
<?= __('Fecha: {0}', $event->event_date) ?>
<?= __('Tipo de boleto: {0}', $ticket->ticket_type_name ?: __('Entrada general')) ?>
<?= __('Importe: {0}', $this->Number->currency((float)$ticket->price, $ticket->currency ?: ($event->currency ?: 'MXN'))) ?>
<?= __('Acceso: Presenta el QR adjunto') ?>

<?= $ticket->get('is_test') ? __('Este es un envio de prueba para validar el diseno del correo y del pase digital. No permite acceso al evento.') : __('El codigo QR es unico. Si lo compartes, otra persona podria usarlo antes que tu.') ?>

<?= trim((string)($event->email_footer ?: __('Conserva este correo y evita compartir tu pase.'))) ?>
