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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Service\TicketRenderer;
use App\Model\Entity\Staff;
use Cake\I18n\DateTime;
use Cake\Utility\Text;

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
        $event = $this->Events->get($id, contain: [
            'TicketConfigurations',
            'Owners',
            'CreatedByUsers',
            'ModifiedByUsers',
            'Staffs' => fn ($query) => $query
                ->contain(['Users'])
                ->where(['Staffs.active' => true])
                ->orderBy(['Staffs.role' => 'ASC', 'Users.names' => 'ASC']),
        ]);

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
            $event->created_by = $this->Authentication->getIdentity()->id;
            $event->modified_by = $this->Authentication->getIdentity()->id;
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
            $event->modified_by = $this->Authentication->getIdentity()->id;

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
                $identity = $this->Authentication->getIdentity();
                $ticketData = array_values($this->request->getData('tickets'));
                foreach ($ticketData as &$ticketRow) {
                    $ticketRow['event_id'] = $event->id;
                    $ticketRow['user_id'] = $identity->id;
                    $ticketRow['registered_by'] = $identity->id;
                    $ticketRow['currency'] = $event->currency ?: 'MXN';
                    $ticketRow['price'] = $ticketRow['price'] ?? '0.00';
                    $ticketRow['payment_status'] = $ticketRow['payment_status'] ?? 'free';
                }
                unset($ticketRow);

                try {
                    $this->saveTicketsWithCapacityControl(
                        $event->id,
                        $identity->id,
                        $ticketData,
                        (bool)$identity->is_superadmin || $event->owner_id === $identity->id
                    );
                    $this->Flash->success(__('El registro ha sido procesado correctamente.'));
                    return $this->redirect(['action' => 'register', $id]);
                } catch (\Throwable $exception) {
                    $this->Flash->error($exception->getMessage());
                }
            }
        }

        $this->set(compact('event', 'tickets'));
    }

    public function addStaff($id)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        $staffsTable = $this->fetchTable('Staffs');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $usersData = array_filter($this->request->getData('users', []), function($value){
                return !empty($value['id']);
            });

            try {
                $staffsTable->getConnection()->transactional(function () use ($staffsTable, $event, $usersData): void {
                    $selectedUserIds = [];
                    foreach ($usersData as $user) {
                        $joinData = $user['_joinData'] ?? $user['_join_data'] ?? [];
                        $role = Staff::normalizeRole($joinData['role'] ?? null);
                        $defaults = Staff::roleDefaults($role);
                        $customMode = !empty($joinData['custom_permissions']);

                        $joinData['role'] = $role;
                        $joinData['role_label'] = Staff::roleOptions()[$role] ?? null;
                        foreach ($defaults as $permission => $value) {
                            $joinData[$permission] = $customMode ? !empty($joinData[$permission]) : $value;
                        }
                        $joinData['register'] = !empty($joinData['can_register']);
                        $joinData['scan'] = !empty($joinData['can_scan']);
                        $joinData['sales_limit'] = ($joinData['sales_limit'] ?? '') === '' ? null : $joinData['sales_limit'];
                        $joinData['active'] = true;
                        unset($joinData['custom_permissions']);

                        $selectedUserIds[] = $user['id'];
                        $staff = $staffsTable->find()
                            ->where([
                                'event_id' => $event->id,
                                'user_id' => $user['id'],
                            ])
                            ->first() ?: $staffsTable->newEmptyEntity();

                        $staff = $staffsTable->patchEntity($staff, $joinData + [
                            'event_id' => $event->id,
                            'user_id' => $user['id'],
                        ]);
                        $staffsTable->saveOrFail($staff);
                    }

                    $conditions = ['event_id' => $event->id];
                    if ($selectedUserIds) {
                        $conditions['user_id NOT IN'] = $selectedUserIds;
                    }
                    $staffsTable->updateAll(['active' => false], $conditions);
                });

                $this->Flash->success(__('El personal del evento ha sido editado correctamente.'));
                return $this->redirect(['action' => 'view', $id]);
            } catch (\Throwable $exception) {
                $this->Flash->error(__('El personal del evento no pudo ser editado. Por favor, intenta de nuevo.'));
            }
        }
        $users = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $staffByUser = $staffsTable->find()
            ->where([
                'event_id' => $event->id,
                'active' => true,
            ])
            ->all()
            ->combine('user_id', fn ($staff) => $staff)
            ->toArray();
        $roleOptions = Staff::roleOptions();
        $roleDescriptions = Staff::roleDescriptions();
        $this->set(compact('event', 'users', 'staffByUser', 'roleOptions', 'roleDescriptions'));
    }

    public function scan($id = null){
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        $this->set(compact('event'));
    }

    public function report($id = null){
        $event = $this->Events->get($id, contain: ['Users', 'Owners']);
        $this->Authorization->authorize($event, 'report');
        $tickets = $this->paginate(
            $this->Events->Tickets->find()
                ->contain(['RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
                ->where(['Tickets.event_id' => $event->id])
                ->orderBy(['Tickets.created' => 'DESC']),
            ['limit' => 75]
        );

        $this->set(compact('event', 'tickets'));
    }

    public function resendTicket($id = null, $ticketId = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'manageTickets');

        $ticket = $this->Events->Tickets->find()
            ->where([
                'Tickets.id' => $ticketId,
                'Tickets.event_id' => $event->id,
            ])
            ->firstOrFail();

        if (!$ticket->active) {
            $this->Flash->warning(__('No es posible reenviar un pase cancelado.'));
            return $this->redirect(['action' => 'register', $event->id]);
        }

        $email = trim((string)$this->request->getData('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->Flash->error(__('Ingresa un correo electronico valido para reenviar el pase.'));
            return $this->redirect(['action' => 'register', $event->id]);
        }

        if ($email !== $ticket->email) {
            $ticket = $this->Events->Tickets->patchEntity($ticket, ['email' => $email]);
            if (!$this->Events->Tickets->save($ticket)) {
                $this->Flash->error(__('El correo del pase no pudo ser actualizado.'));
                return $this->redirect(['action' => 'register', $event->id]);
            }
        }

        try {
            $this->Events->Tickets->deliverTicketEmail($ticket);
            $this->Flash->success(__('El pase fue reenviado a {0}.', $ticket->email));
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            $this->Flash->error(__('No fue posible reenviar el pase. Revisa la configuracion de correo e intenta nuevamente.'));
        }

        return $this->redirect(['action' => 'register', $event->id]);
    }

    public function cancelTicket($id = null, $ticketId = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'manageTickets');

        $reason = trim((string)$this->request->getData('cancelled_reason'));
        $connection = $this->Events->getConnection();

        try {
            $folio = null;
            $connection->transactional(function () use ($connection, $event, $ticketId, $reason, &$folio): void {
                $ticket = $this->Events->Tickets->find()
                    ->where([
                        'Tickets.id' => $ticketId,
                        'Tickets.event_id' => $event->id,
                    ])
                    ->epilog('FOR UPDATE')
                    ->firstOrFail();

                if (!$ticket->active) {
                    throw new \RuntimeException(__('Este pase ya esta cancelado.'));
                }

                if ($ticket->attended) {
                    throw new \RuntimeException(__('No es posible cancelar un pase que ya fue escaneado en el acceso.'));
                }

                $ticket = $this->Events->Tickets->patchEntity($ticket, [
                    'active' => false,
                    'cancelled' => DateTime::now(),
                    'cancelled_by' => $this->Authentication->getIdentity()->id,
                    'cancelled_reason' => $reason !== '' ? $reason : null,
                ]);
                $this->Events->Tickets->saveOrFail($ticket);

                if ($ticket->registered_by) {
                    $connection->execute(
                        'UPDATE staffs
                         SET sales_count = GREATEST(sales_count - 1, 0)
                         WHERE event_id = ? AND user_id = ?',
                        [$event->id, $ticket->registered_by]
                    );
                }

                $folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
            });

            $this->Flash->success(__('El pase {0} fue cancelado y el cupo quedo disponible.', $folio));
        } catch (\RuntimeException $exception) {
            $this->Flash->warning($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            $this->Flash->error(__('El pase no pudo ser cancelado. Por favor, intenta nuevamente.'));
        }

        return $this->redirect(['action' => 'register', $event->id]);
    }

    public function exportSales($id = null)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'report');
        $tickets = $this->Events->Tickets->find()
            ->contain(['RegisteredByUsers'])
            ->where(['Tickets.event_id' => $event->id])
            ->all();

        $rows = [[__('Responsable'), __('Boletos activos'), __('Cancelados'), __('Monto total')]];
        $summary = [];
        foreach ($tickets as $ticket) {
            $seller = $ticket->registered_by_user->full_name ?? __('Sin responsable');
            $summary[$seller] ??= ['active' => 0, 'cancelled' => 0, 'amount' => 0.0];
            if ($ticket->active) {
                $summary[$seller]['active']++;
                $summary[$seller]['amount'] += (float)$ticket->price;
            } else {
                $summary[$seller]['cancelled']++;
            }
        }
        ksort($summary);
        foreach ($summary as $seller => $totals) {
            $rows[] = [$seller, $totals['active'], $totals['cancelled'], $totals['amount']];
        }

        return $this->downloadSpreadsheet($event, __('balance-ventas'), $rows);
    }

    public function exportAttendance($id = null)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'report');
        $tickets = $this->Events->Tickets->find()
            ->contain(['RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
            ->where(['Tickets.event_id' => $event->id])
            ->orderBy(['Tickets.folio' => 'ASC'])
            ->all();

        $rows = [[__('Folio'), __('Nombre'), __('Correo'), __('Registrado por'), __('Asistencia'), __('Escaneado por'), __('Estado'), __('Cancelado'), __('Cancelado por')]];
        foreach ($tickets as $ticket) {
            $rows[] = [
                str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT),
                $ticket->name,
                $ticket->email,
                $ticket->registered_by_user->full_name ?? '',
                $ticket->attended ? $ticket->attended->i18nFormat('yyyy-MM-dd HH:mm:ss') : '',
                $ticket->checked_in_user->full_name ?? '',
                $ticket->active ? __('Activo') : __('Cancelado'),
                $ticket->cancelled ? $ticket->cancelled->i18nFormat('yyyy-MM-dd HH:mm:ss') : '',
                $ticket->cancelled_by_user->full_name ?? '',
            ];
        }

        return $this->downloadSpreadsheet($event, __('asistencia'), $rows);
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

    private function saveTicketsWithCapacityControl(string $eventId, string $userId, array $ticketData, bool $isPrivilegedUser): void
    {
        $quantity = count($ticketData);
        if ($quantity === 0) {
            throw new \RuntimeException(__('No hay pases para emitir.'));
        }

        $connection = $this->Events->getConnection();
        $connection->transactional(function () use ($connection, $eventId, $userId, $ticketData, $quantity, $isPrivilegedUser): void {
            $lockedEvent = $connection->execute(
                'SELECT id, capacity, ticket_count, owner_id FROM events WHERE id = ? FOR UPDATE',
                [$eventId]
            )->fetch('assoc');

            if (!$lockedEvent) {
                throw new \RuntimeException(__('El evento no esta disponible.'));
            }

            $available = max(0, (int)$lockedEvent['capacity'] - (int)$lockedEvent['ticket_count']);
            if ($quantity > $available) {
                throw new \RuntimeException(__('No hay cupo suficiente. Disponibles: {0}.', $available));
            }

            $staff = $connection->execute(
                'SELECT id, sales_limit, sales_count, can_register, register FROM staffs WHERE event_id = ? AND user_id = ? AND active = 1 FOR UPDATE',
                [$eventId, $userId]
            )->fetch('assoc');

            if (!$isPrivilegedUser && $staff && !$staff['can_register'] && !$staff['register']) {
                throw new \RuntimeException(__('Este usuario no tiene permiso para emitir pases en el evento.'));
            }

            if (!$isPrivilegedUser && $staff && $staff['sales_limit'] !== null) {
                $remaining = max(0, (int)$staff['sales_limit'] - (int)$staff['sales_count']);
                if ($quantity > $remaining) {
                    throw new \RuntimeException(__('El limite de venta de este usuario permite emitir {0} pases mas.', $remaining));
                }
            }

            $tickets = $this->Events->Tickets->newEntities($ticketData);
            $this->Events->Tickets->saveManyOrFail($tickets);

            if ($staff) {
                $connection->execute(
                    'UPDATE staffs SET sales_count = sales_count + ? WHERE id = ?',
                    [$quantity, $staff['id']]
                );
            }
        });
    }

    private function downloadSpreadsheet($event, string $reportName, array $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows);
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = Text::slug($event->name . '-' . $reportName) . '.xlsx';
        $path = tempnam(TMP, 'eventic-report-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $this->response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withDownload($filename)
            ->withFile($path, ['download' => true, 'name' => $filename]);
    }
}
