<?= __('Pase digital confirmado: {0}', $event->name) ?>

<?= __('Hola {0},', $ticket->name) ?>

<?= trim((string)($event->email_message ?: \App\Utility\EventDefaults::emailMessage($event))) ?>

<?= __('Folio: {0}', $ticket->get('is_test') ? __('PRUEBA') : str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)) ?>

<?= __('Fecha: {0}', $event->event_date ? $event->event_date->format('d/m/Y, H:i') : __('Por confirmar')) ?>

<?= __('Tipo de boleto: {0}', $ticket->ticket_type_name ?: __('Entrada general')) ?>

<?= __('Importe: {0}', $this->Number->currency((float)$ticket->price, $ticket->currency ?: ($event->currency ?: 'MXN'))) ?>

<?= __('Ubicación: {0}', $event->location ?: __('Por confirmar')) ?>

<?= __('Acceso: presenta el QR del boleto PNG adjunto. Guárdalo en tu teléfono antes del evento.') ?>

<?= $ticket->get('is_test') ? __('Este es un envío de prueba para validar el diseño del correo y del pase digital. No permite acceso al evento.') : __('El código QR es único. Si lo compartes, otra persona podría usarlo antes que tú.') ?>

<?= trim((string)($event->email_footer ?: \App\Utility\EventDefaults::emailFooter($event))) ?>
