<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Exception\RecordNotFoundException;

class TicketsController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
    }

    public function attend($id = null)
    {
        $this->request->allowMethod(['post']);
        $eventId = $this->request->getData('event_id');
        $identity = $this->request->getAttribute('identity');
        $ticketId = $this->normalizeTicketId((string)$id);

        if (!$eventId || !$identity || !$ticketId) {
            return $this->responseBad([
                'message' => __('No se pudo validar el evento o el usuario que escanea.'),
                'status' => 'invalid_request',
            ]);
        }

        $eventsTable = FactoryLocator::get('Table')->get('Events');

        try {
            $event = $eventsTable->get($eventId);
        } catch (RecordNotFoundException $exception) {
            return $this->responseStatus(404, [
                'message' => __('No se encontro el evento de validacion.'),
                'status' => 'invalid_event',
            ]);
        }

        $this->Authorization->authorize($event, 'scan');

        $ticket = $this->Tickets->find()
            ->contain(['Events', 'CheckedInUsers'])
            ->where(['Tickets.id' => $ticketId])
            ->first();

        if (!$ticket || !$ticket->active) {
            return $this->responseStatus(404, [
                'message' => __('Pase no valido. Verifica que el QR pertenezca a un boleto activo.'),
                'status' => 'invalid',
            ]);
        }

        if ($ticket->event_id !== $eventId) {
            return $this->responseStatus(422, [
                'message' => __('Este pase pertenece a otro evento.'),
                'status' => 'wrong_event',
                'data' => $this->ticketPayload($ticket),
            ]);
        }

        if ($ticket->attended) {
            return $this->responseStatus(409, [
                'message' => __('Pase ya utilizado. No permitas el acceso nuevamente.'),
                'status' => 'duplicate',
                'data' => $this->ticketPayload($ticket),
            ]);
        }

        $checkedAt = DateTime::now();
        $affected = $this->Tickets->updateAll(
            [
                'attended' => $checkedAt,
                'checked_in_by' => $identity->id,
                'checked_in_ip' => $this->request->clientIp(),
                'checked_in_user_agent' => substr($this->request->getHeaderLine('User-Agent'), 0, 255),
            ],
            [
                'id' => $ticketId,
                'event_id' => $eventId,
                'active' => true,
                'attended IS' => null,
            ]
        );

        $ticket = $this->Tickets->find()
            ->contain(['Events', 'CheckedInUsers'])
            ->where(['Tickets.id' => $ticketId])
            ->first();

        if ($affected !== 1) {
            return $this->responseStatus(409, [
                'message' => __('Pase ya utilizado. No permitas el acceso nuevamente.'),
                'status' => 'duplicate',
                'data' => $this->ticketPayload($ticket),
            ]);
        }

        $eventsTable->getConnection()->execute(
            'UPDATE events SET ticket_attended_count = ticket_attended_count + 1 WHERE id = ?',
            [$eventId]
        );

        return $this->responseOK([
            'message' => __('Pase validado. Acceso autorizado.'),
            'status' => 'valid',
            'data' => $this->ticketPayload($ticket)
        ]);

    }

    private function normalizeTicketId(string $value): ?string
    {
        $value = trim(urldecode($value));
        if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $value, $matches)) {
            return strtolower($matches[0]);
        }

        return null;
    }

    private function ticketPayload($ticket): array
    {
        if (!$ticket) {
            return [];
        }

        return [
            'id' => $ticket->id,
            'folio' => str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT),
            'name' => $ticket->name,
            'email' => $ticket->email,
            'event' => $ticket->event->name ?? null,
            'attended' => $ticket->attended ? $ticket->attended->i18nFormat('dd MMM yyyy, HH:mm:ss') : null,
            'checked_in_by' => $ticket->checked_in_user->full_name ?? null,
            'payment_status' => $ticket->payment_status ?: 'free',
        ];
    }

    public function cancel($id)
    {
        $ticket = $this->Tickets->get($id, contain:['Users', 'Events']);
        $ticket->active = false;
        if ($this->Tickets->save($ticket))
        {
            $ticketsImg[$ticket->id] = WWW_ROOT . "files/tickets/{$ticket->id}.png";
            $mailer = new Mailer('default');
                $mailer->setAttachments($ticketsImg)
                ->setEmailFormat('html')
                ->setTo($ticket->user->email)
                ->setSubject("{$ticket->event->name}: Cancelación de Boleto")
                ->viewBuilder()
                ->setTemplate('cancel');
            $mailer->deliver();
            $this->Flash->success(__('El ticket fue cancelado con éxito.'));

        } else {
            $this->Flash->error(__('EL ticket no fue cancelado con éxito, por favor intentelo nuevamente.'));
        }
        return $this->redirect($this->request->referer());
    }


}
