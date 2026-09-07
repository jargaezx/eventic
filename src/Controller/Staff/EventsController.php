<?php
declare(strict_types=1);

namespace App\Controller\Staff;

class EventsController extends AppController
{
    public function index()
    {
        $identity = $this->request->getAttribute('identity');
        $events = $this->fetchTable('Events')->find(
            'my',
            user: $identity->getOriginalData(),
            contain: ['Owners'],
            order: ['Events.event_date' => 'ASC']
        )->all();

        $this->set(compact('events'));
    }

    public function view(string $id)
    {
        $event = $this->fetchTable('Events')->get($id, contain: [
            'Owners',
            'Tickets' => [
                'sort' => ['Tickets.created' => 'DESC'],
                'limit' => 50,
            ],
        ]);
        $this->Authorization->authorize($event, 'view');

        $this->set(compact('event'));
    }

    public function scan(string $id)
    {
        $event = $this->fetchTable('Events')->get($id, contain: ['Owners']);
        $this->Authorization->authorize($event, 'scan');

        $this->set(compact('event'));
    }
}
