<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use App\Service\TicketRenderer;

class EventsController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Search.Search', [
            'actions' => ['index', 'lookup'],
        ]);
    }

    public function index()
    {
        $query = $this->Events->find(
            'search',
            search: $this->request->getQueryParams(),
            contain: ['Owners', 'Users'],
        );
        $events = $this->paginate($query);

        $this->set(compact('events'));
    }

    public function view($id = null)
    {
        $event = $this->Events->get($id, contain: ['TicketConfigurations', 'Owners', 'Users']);

        $ticketPreview = null;
        if ($event->ticket_configuration) {
            try {
                $ticketPreview = (new TicketRenderer())->renderPreview($event);
            } catch (\Throwable $exception) {
                $this->log($exception->getMessage(), 'error');
                $this->Flash->warning(__('La vista previa del pase no esta disponible.'));
            }
        }

        $this->set(compact('event', 'ticketPreview'));
    }

    public function add()
    {
        $event = $this->Events->newEmptyEntity();

        if ($this->request->is('post')) {

            $event = $this->Events->patchEntity($event, $this->request->getData(), ['associated' => ['TicketConfigurations']]);
            $event->owner_id = $this->Authentication->getIdentity()->id;
            if ($this->Events->save($event)) {
                $this->Flash->success(__('El evento ha sido creado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El evento no pudo ser creado. Por favor, intenta de nuevo.'));
        }
        $owners = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $this->set(compact('event', 'owners'));
    }

    public function edit($id = null)
    {
        $event = $this->Events->get($id, contain: ['TicketConfigurations']);
        $this->Authorization->authorize($event);
        if ($this->request->is(['patch', 'post', 'put'])) {

            $event = $this->Events->patchEntity($event, $this->request->getData(), ['associated' => ['TicketConfigurations']]);

            if ($this->Events->save($event)) {
                $this->Flash->success(__('El evento ha sido editado correctamente.'));
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('El evento no pudo ser editado. Por favor, intenta de nuevo.'));
        }
        $owners = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $this->set(compact('event', 'owners'));
    }

    public function editQR($id = null)
    {
        $event = $this->Events->get($id, contain: ['TicketConfigurations']);
        $this->Authorization->authorize($event);
        if ($this->request->is(['patch', 'post', 'put'])) {

            $event->ticket_configuration = $this->Events->TicketConfigurations->patchEntity($event->ticket_configuration, $this->request->getData());

            if ($this->Events->TicketConfigurations->save($event->ticket_configuration)) {
                $this->Flash->success(__('El boleto ha sido editado correctamente.'));
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('El boleto no pudo ser editado. Por favor, intenta de nuevo.'));
        }

        $this->set(['ticketConfiguration' => $event->ticket_configuration]);
    }

    public function register($id)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        $tickets = $this->paginate(
            $this->Events->Tickets->find()
                ->where(['Tickets.event_id' => $event->id])
                ->orderBy(['Tickets.folio' => 'DESC']),
            ['limit' => 50]
        );

        $this->set(compact('event', 'tickets'));
    }

    public function checkout($id)
    {

        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'register');
        $n = $this->request->getQuery('n', 0);
        $tickets = array_fill(0, (int)$n, ['', '']);

        if ($this->request->is(['patch', 'post', 'put'])) {

            if ($this->request->getData('file')) {
                $tmpFile = $this->request->getUploadedFile('file')->getStream()->getMetadata('uri');
                $spreadsheet = IOFactory::load($tmpFile);
                $tickets = array_filter($spreadsheet->getActiveSheet()->toArray(), function ($value, $key) {
                    if ($key == 0 || (empty(trim((string)$value[0])) && empty(trim((string)$value[1])))) return false;
                    return true;
                }, ARRAY_FILTER_USE_BOTH);
            }

            if ($this->request->getData('tickets')) {
                $available = max(0, (int)$event->capacity - (int)$event->ticket_count);
                if (count($this->request->getData('tickets')) > $available) {
                    $this->Flash->error(__('No hay cupo suficiente. Disponibles: {0}.', $available));
                    return $this->redirect(['action' => 'register', $id]);
                }
                $tickets = $this->Events->Tickets->newEntities($this->request->getData('tickets'));
                if ($this->Events->Tickets->saveMany($tickets)) {
                    $this->Flash->success(__('El registro ha sido procesado correctamente.'));
                    return $this->redirect(['action' => 'register', $id]);
                }
                $this->Flash->error(__('El registro no pudo ser procesado correctamente. Por favor, intenta de nuevo.'));
            }
        }

        $this->set(compact('event', 'tickets'));
    }

    public function addStaff($id)
    {
        $event = $this->Events->get($id, contain: ['Users']);
        $this->Authorization->authorize($event);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data['users'] = array_filter($this->request->getData('users', []), function($value){
                return !empty($value['id']);
            });
            //dd($data);
            $event = $this->Events->patchEntity($event, $data, ['associated' => ['Users']]);
            if ($this->Events->save($event)) {
                $this->Flash->success(__('El personal del evento ha sido editado correctamente.'));
                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('El personal del evento no pudo ser editado. Por favor, intenta de nuevo.'));
        }
        $users = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $this->set(compact('event', 'users'));
    }

    public function scan($id = null){
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        $this->set(compact('event'));
    }

    public function report($id = null){
        $event = $this->Events->get($id, contain: ['Users', 'Owners']);
        $this->Authorization->authorize($event, 'view');
        $tickets = $this->paginate(
            $this->Events->Tickets->find()
                ->where(['Tickets.event_id' => $event->id])
                ->orderBy(['Tickets.created' => 'DESC']),
            ['limit' => 75]
        );

        $this->set(compact('event', 'tickets'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        if ($this->Events->trash($event)) {
            $this->Flash->success(__('El evento ha sido eliminado.'));
        } else {
            $this->Flash->error(__('El evento no pudo ser eliminado. Por favor, intente nuevamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
