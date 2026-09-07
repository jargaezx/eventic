<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use Cake\Mailer\MailerAwareTrait;

class UsersController extends AppController
{
    use MailerAwareTrait;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Search.Search', [
            'actions' => ['index', 'lookup'],
        ]);
    }

    public function dashboard()
    {
        if ($this->request->getQuery('redirectUrl') || $this->request->getQuery('redirect')) {
            return $this->redirect(['action' => 'dashboard']);
        }

        $currentUser = $this->request->getAttribute('identity');
        $myEvents = $this->Users->Events->find('my',
            user:$currentUser->getOriginalData(),
            contain: ['Owners', 'Users', 'Tickets' => [
                'sort' => ['Tickets.created' => 'DESC'],
            ]],
            order: ['Events.event_date' => 'ASC' ]
        )->all();

        $dashboard = [
            'events' => $myEvents->count(),
            'capacity' => 0,
            'tickets' => 0,
            'attended' => 0,
            'available' => 0,
            'occupancy' => 0,
            'checkin' => 0,
            'recentTickets' => [],
        ];

        foreach ($myEvents as $event) {
            $dashboard['capacity'] += (int)$event->capacity;
            $dashboard['tickets'] += (int)$event->ticket_count;
            $dashboard['attended'] += (int)$event->ticket_attended_count;
            foreach ($event->tickets ?? [] as $ticket) {
                $dashboard['recentTickets'][] = ['event' => $event, 'ticket' => $ticket];
            }
        }

        $dashboard['available'] = max(0, $dashboard['capacity'] - $dashboard['tickets']);
        $dashboard['occupancy'] = $dashboard['capacity'] > 0 ? round(($dashboard['tickets'] / $dashboard['capacity']) * 100, 1) : 0;
        $dashboard['checkin'] = $dashboard['tickets'] > 0 ? round(($dashboard['attended'] / $dashboard['tickets']) * 100, 1) : 0;
        usort($dashboard['recentTickets'], fn ($a, $b) => $b['ticket']->created <=> $a['ticket']->created);
        $dashboard['recentTickets'] = array_slice($dashboard['recentTickets'], 0, 8);

        $this->set(compact('myEvents', 'dashboard'));
    }

    public function index()
    {
        $query = $this->Users->find('search',
            search: $this->request->getQueryParams(),
            contain: ['Roles'],
           //conditions: ['Users.is_superadmin' => false]
        );
        $users = $this->paginate($query);

        $roles = $this->Users->Roles->find('list');
        $this->set(compact('users', 'roles'));
    }

    public function view($id = null)
    {
        $user = $this->Users->get($id, contain: ['Roles']);
        $this->set(compact('user'));
    }

    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('El usuario ha sido creado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('El usuario no pudo ser creado. Por favor, intenta de nuevo.'));
        }

        $roles = $this->Users->Roles->find('list');
        $this->set(compact('user', 'roles'));
    }

    public function edit($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('El usuario ha sido editado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('El usuario no pudo ser editado. Por favor, intenta de nuevo.'));
        }

        $roles = $this->Users->Roles->find('list');
        $this->set(compact('user', 'roles'));
    }

    public function changePassword($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => [],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->getMailer('User')->send('changePassword', [$user]);
                $this->Flash->success(__('La contraseña fue cambiada correctamente.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('La contraseña no pudo ser cambiada. Por favor, intente nuevamente.'));
        }


        $this->set(compact('user'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->trash($user)) {
            $this->Flash->success(__('El usuario ha sido eliminado.'));
        } else {
            $this->Flash->error(__('El usuario no pudo ser eliminado. Por favor, intente nuevamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
