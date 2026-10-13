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
        $eventIds = collection($events)->extract('id')->toList();
        $assignments = [];
        if ($eventIds) {
            $assignments = $this->fetchTable('Staffs')->find()
                ->where([
                    'event_id IN' => $eventIds,
                    'user_id' => $identity->id,
                    'active' => true,
                ])
                ->all()
                ->combine('event_id', fn ($staff) => $staff)
                ->toArray();
        }

        $this->set(compact('events', 'assignments'));
    }

    public function view(string $id)
    {
        $event = $this->fetchTable('Events')->get($id, contain: ['Owners']);
        $this->Authorization->authorize($event, 'view');
        $identity = $this->request->getAttribute('identity');
        $assignment = $this->fetchTable('Staffs')->userAssignment($event->id, $identity->id);
        $tickets = $this->fetchTable('Tickets')->find()
            ->where(['Tickets.event_id' => $event->id])
            ->contain(['CheckedInUsers'])
            ->orderBy(['Tickets.created' => 'DESC'])->limit(50);
        if (!$identity->can('viewAllTickets', $event)) {
            $conditions = [];
            if ($assignment && $assignment->can_register) {
                $conditions['Tickets.registered_by'] = $identity->id;
            }
            if ($assignment && $assignment->can_scan) {
                $conditions['Tickets.checked_in_by'] = $identity->id;
            }
            $tickets->where($conditions ? ['OR' => $conditions] : ['Tickets.id IS' => null]);
        }
        $event->tickets = $tickets->all()->toList();

        $this->set(compact('event', 'assignment'));
    }

    public function scan(string $id)
    {
        $event = $this->fetchTable('Events')->get($id, contain: ['Owners']);
        $this->Authorization->authorize($event, 'scan');

        $this->set(compact('event'));
    }
}
