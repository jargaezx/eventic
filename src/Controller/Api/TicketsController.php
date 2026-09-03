<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Datasource\FactoryLocator;

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

        if (!$eventId || !$identity) {
            return $this->responseBad([
                'message' => __('No se pudo validar el evento o el usuario que escanea.'),
            ]);
        }

        $eventsTable = FactoryLocator::get('Table')->get('Events');
        $event = $eventsTable->get($eventId);
        $this->Authorization->authorize($event, 'scan');

        $ticket = $this->Tickets->find('all', conditions: [
            'id' => $id,
            'event_id' => $eventId,
            'attended IS' => NULL,
            'active' => true
        ])->first();

        if(!$ticket)
        {
            return $this->responseBad([
                'message'    => __('El boleto no es válido o ya ha sido utilizado.')
            ]);
        }

        $ticket->attended = DateTime::now();
        $ticket->checked_in_by = $identity?->id;
        $ticket->checked_in_ip = $this->request->clientIp();
        $ticket->checked_in_user_agent = substr($this->request->getHeaderLine('User-Agent'), 0, 255);

        if (!$this->Tickets->save($ticket)) {
            return $this->responseBad([
                'errors'    => $ticket->getErrors()
            ]);
        }

        return $this->responseOK([
            'message'    => __('Boleto validado correctamente.'),
            'data'  => $ticket
        ]);

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
