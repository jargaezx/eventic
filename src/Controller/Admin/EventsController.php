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
            'TicketTypes' => fn ($query) => $query
                ->contain(['TicketRates'])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
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
            $data = $this->normalizeTicketCatalogData($this->request->getData());
            $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes.TicketRates']]);
            $event->owner_id = $this->Authentication->getIdentity()->id;
            $event->created_by = $this->Authentication->getIdentity()->id;
            $event->modified_by = $this->Authentication->getIdentity()->id;
            if ($this->Events->save($event)) {
                $this->Flash->success(__('El evento ha sido creado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El evento no pudo ser creado. Por favor, intenta de nuevo.'));
        } else {
            $event->ticket_types = $this->defaultTicketCatalog();
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
        $event = $this->Events->get($id, contain: [
            'TicketConfigurations',
            'TicketTypes' => fn ($query) => $query
                ->contain(['TicketRates'])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->normalizeTicketCatalogData($this->request->getData());
            $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes.TicketRates']]);
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
            $qrData = $this->request->getData();
            $hasQrData = array_intersect(['x', 'y', 'qr_size'], array_keys($qrData));
            if (!$hasQrData) {
                $this->Flash->error(__('No se recibieron cambios para la configuracion del QR.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            $event->ticket_configuration = $this->Events->TicketConfigurations->patchEntity($event->ticket_configuration, $qrData);

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
        $q = trim((string)$this->request->getQuery('q'));
        $status = (string)$this->request->getQuery('status', 'active');
        $attendance = (string)$this->request->getQuery('attendance', 'all');
        $delivery = (string)$this->request->getQuery('delivery', 'all');

        $query = $this->Events->Tickets->find()
            ->contain(['TicketTypes', 'TicketRates', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
            ->where(['Tickets.event_id' => $event->id])
            ->orderBy(['Tickets.folio' => 'DESC']);

        if ($q !== '') {
            $query->where(function ($exp) use ($q) {
                $or = [
                    'Tickets.name LIKE' => '%' . $q . '%',
                    'Tickets.email LIKE' => '%' . $q . '%',
                ];
                if (ctype_digit($q)) {
                    $or['Tickets.folio'] = (int)$q;
                }

                return $exp->or($or);
            });
        }

        if ($status === 'active') {
            $query->where(['Tickets.active' => true]);
        } elseif ($status === 'cancelled') {
            $query->where(['Tickets.active' => false]);
        }

        if ($attendance === 'checked') {
            $query->where(['Tickets.attended IS NOT' => null]);
        } elseif ($attendance === 'pending') {
            $query->where(['Tickets.attended IS' => null]);
        }

        if ($delivery === 'sent') {
            $query->where(['Tickets.last_emailed IS NOT' => null]);
        } elseif ($delivery === 'not_sent') {
            $query->where(['Tickets.last_emailed IS' => null]);
        }

        $tickets = $this->paginate(
            $query,
            ['limit' => 50]
        );

        $filters = compact('q', 'status', 'attendance', 'delivery');
        $this->set(compact('event', 'tickets', 'filters'));
    }

    public function ticket($id = null, $ticketId = null)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'manageTickets');
        $ticket = $this->Events->Tickets->find()
            ->contain(['Events', 'TicketTypes', 'TicketRates', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
            ->where([
                'Tickets.id' => $ticketId,
                'Tickets.event_id' => $event->id,
            ])
            ->firstOrFail();

        $this->set(compact('event', 'ticket'));
    }

    public function checkout($id)
    {

        $event = $this->Events->get($id, contain: [
            'TicketTypes' => fn ($query) => $query
                ->contain(['TicketRates' => fn ($rateQuery) => $rateQuery
                    ->where(['TicketRates.active' => true])
                    ->orderBy(['TicketRates.sort_order' => 'ASC', 'TicketRates.name' => 'ASC'])])
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'register');
        $rateCatalog = $this->buildRateCatalog($event);
        if (!$rateCatalog) {
            $this->Flash->warning(__('Configura al menos una tarifa activa antes de emitir pases.'));
            return $this->redirect(['action' => 'edit', $event->id]);
        }
        $firstRateId = array_key_first($rateCatalog);
        $n = $this->request->getQuery('n', 1);
        $tickets = [];
        for ($i = 0; $i < max(1, (int)$n); $i++) {
            $tickets[] = [
                'name' => '',
                'email' => '',
                'ticket_rate_id' => $firstRateId,
                'payment_status' => 'free',
            ];
        }

        if ($this->request->is(['patch', 'post', 'put'])) {

            try {
                if ($this->request->getData('file')) {
                    $tmpFile = $this->request->getUploadedFile('file')->getStream()->getMetadata('uri');
                    $spreadsheet = IOFactory::load($tmpFile);
                    $tickets = array_values(array_filter($spreadsheet->getActiveSheet()->toArray(), function ($value, $key) {
                        if ($key == 0 || (empty(trim((string)$value[0])) && empty(trim((string)$value[1])))) return false;
                        return true;
                    }, ARRAY_FILTER_USE_BOTH));
                    $tickets = array_map(fn ($row) => [
                        'name' => trim((string)($row[0] ?? '')),
                        'email' => strtolower(trim((string)($row[1] ?? ''))),
                        'ticket_rate_id' => $this->resolveRateKey((string)($row[2] ?? ''), $rateCatalog, $firstRateId),
                        'payment_status' => isset($row[3]) && $row[3] !== '' ? (string)$row[3] : 'free',
                    ], $tickets);
                }
            } catch (\Throwable $exception) {
                $this->Flash->error($exception->getMessage());
            }

            if ($this->request->getData('tickets')) {
                $identity = $this->Authentication->getIdentity();
                $ticketData = $this->prepareTicketRows(
                    (array)$this->request->getData('tickets'),
                    (string)$this->request->getData('buyer_email', ''),
                    $rateCatalog
                );
                foreach ($ticketData as $index => &$ticketRow) {
                    $ticketRow['event_id'] = $event->id;
                    $ticketRow['user_id'] = $identity->id;
                    $ticketRow['registered_by'] = $identity->id;
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

        $paymentStatuses = [
            'free' => __('Gratis'),
            'pending' => __('Pendiente'),
            'paid' => __('Pagado'),
        ];
        $batchTotal = array_sum(array_map(fn ($ticket) => (float)($ticket['price'] ?? 0), $tickets));
        if (!$batchTotal) {
            $batchTotal = array_sum(array_map(fn ($ticket) => (float)($rateCatalog[$ticket['ticket_rate_id']]['price'] ?? 0), $tickets));
        }
        $rateOptions = [];
        $rateMeta = [];
        foreach ($rateCatalog as $rateId => $rate) {
            $rateOptions[$rateId] = $rate['label'];
            $rateMeta[$rateId] = [
                'price' => (float)$rate['price'],
                'currency' => $rate['currency'],
                'isFree' => (float)$rate['price'] <= 0,
            ];
        }

        $this->set(compact('event', 'tickets', 'paymentStatuses', 'batchTotal', 'rateOptions', 'rateMeta'));
    }

    public function downloadBulkTemplate($id)
    {
        $event = $this->Events->get($id, contain: [
            'TicketTypes' => fn ($query) => $query
                ->contain(['TicketRates' => fn ($rateQuery) => $rateQuery
                    ->where(['TicketRates.active' => true])
                    ->orderBy(['TicketRates.sort_order' => 'ASC', 'TicketRates.name' => 'ASC'])])
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'register');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('Formato pases'));
        $rateCatalog = $this->buildRateCatalog($event);
        $sampleRate = $rateCatalog ? reset($rateCatalog)['label'] : __('Entrada general - General');
        $sheet->fromArray([
            [__('nombre'), __('correo_entrega'), __('tarifa'), __('estado_pago')],
            [__('Nombre del asistente'), __('comprador@empresa.com'), $sampleRate, 'free'],
        ]);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'formato-carga-pases-' . Text::slug(strtolower((string)$event->name)) . '.xlsx';
        $path = TMP . $filename;
        $writer->save($path);

        return $this->response->withFile($path, ['download' => true, 'name' => $filename]);
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
                ->contain(['TicketTypes', 'TicketRates', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
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
            ->contain(['TicketTypes', 'TicketRates', 'RegisteredByUsers'])
            ->where(['Tickets.event_id' => $event->id])
            ->all();

        $rows = [[__('Responsable'), __('Tipo'), __('Tarifa'), __('Boletos activos'), __('Cancelados'), __('Monto total')]];
        $summary = [];
        foreach ($tickets as $ticket) {
            $seller = $ticket->registered_by_user->full_name ?? __('Sin responsable');
            $type = $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? __('Sin tipo'));
            $rate = $ticket->ticket_rate_name ?: ($ticket->ticket_rate->name ?? __('Sin tarifa'));
            $key = implode('|', [$seller, $type, $rate]);
            $summary[$key] ??= [
                'seller' => $seller,
                'type' => $type,
                'rate' => $rate,
                'active' => 0,
                'cancelled' => 0,
                'amount' => 0.0,
            ];
            if ($ticket->active) {
                $summary[$key]['active']++;
                $summary[$key]['amount'] += (float)$ticket->price;
            } else {
                $summary[$key]['cancelled']++;
            }
        }
        ksort($summary);
        $grandTotal = 0.0;
        foreach ($summary as $totals) {
            $grandTotal += (float)$totals['amount'];
            $rows[] = [$totals['seller'], $totals['type'], $totals['rate'], $totals['active'], $totals['cancelled'], $totals['amount']];
        }
        $rows[] = [__('Total'), '', '', '', '', $grandTotal];

        return $this->downloadSpreadsheet($event, __('balance-ventas'), $rows);
    }

    public function exportAttendance($id = null)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'report');
        $tickets = $this->Events->Tickets->find()
            ->contain(['TicketTypes', 'TicketRates', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
            ->where(['Tickets.event_id' => $event->id])
            ->orderBy(['Tickets.folio' => 'ASC'])
            ->all();

        $rows = [[__('Folio'), __('Nombre'), __('Correo'), __('Tipo'), __('Tarifa'), __('Importe'), __('Pago'), __('Registrado por'), __('Asistencia'), __('Escaneado por'), __('Estado'), __('Cancelado'), __('Cancelado por')]];
        foreach ($tickets as $ticket) {
            $rows[] = [
                str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT),
                $ticket->name,
                $ticket->email,
                $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? ''),
                $ticket->ticket_rate_name ?: ($ticket->ticket_rate->name ?? ''),
                (float)$ticket->price,
                $ticket->payment_status,
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

            $this->assertTicketCatalogAvailability($connection, $eventId, $ticketData);

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

    private function prepareTicketRows(array $rows, string $buyerEmail = '', array $rateCatalog = []): array
    {
        $prepared = [];
        $buyerEmail = strtolower(trim($buyerEmail));
        foreach (array_values($rows) as $row) {
            $name = trim((string)($row['name'] ?? ''));
            $email = strtolower(trim((string)($row['email'] ?? '')));
            $email = $email !== '' ? $email : $buyerEmail;
            if ($name === '' && $email === '') {
                continue;
            }
            if ($name === '' || $email === '') {
                throw new \RuntimeException(__('Cada pase debe tener nombre y correo de entrega. Puedes usar el correo principal para no repetirlo.'));
            }
            $rateId = (string)($row['ticket_rate_id'] ?? '');
            if ($rateId === '' || !isset($rateCatalog[$rateId])) {
                throw new \RuntimeException(__('Selecciona una tarifa valida para todos los pases.'));
            }
            $rate = $rateCatalog[$rateId];
            $price = (float)$rate['price'];
            $paymentStatus = (string)($row['payment_status'] ?? '');
            if ($price <= 0) {
                $paymentStatus = 'free';
            } elseif (!in_array($paymentStatus, ['pending', 'paid'], true)) {
                $paymentStatus = 'paid';
            }
            $prepared[] = [
                'name' => $name,
                'email' => $email,
                'ticket_type_id' => $rate['ticket_type_id'],
                'ticket_rate_id' => $rateId,
                'ticket_type_name' => $rate['ticket_type_name'],
                'ticket_rate_name' => $rate['name'],
                'price' => number_format($price, 2, '.', ''),
                'currency' => $rate['currency'],
                'payment_status' => $paymentStatus,
            ];
        }

        return $prepared;
    }

    private function normalizeTicketCatalogData(array $data): array
    {
        $currency = strtoupper(trim((string)($data['currency'] ?? 'MXN'))) ?: 'MXN';
        $types = [];
        foreach (array_values((array)($data['ticket_types'] ?? [])) as $typeIndex => $type) {
            $name = trim((string)($type['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $rates = [];
            foreach (array_values((array)($type['ticket_rates'] ?? [])) as $rateIndex => $rate) {
                $rateName = trim((string)($rate['name'] ?? ''));
                if ($rateName === '') {
                    continue;
                }
                $rates[] = [
                    'id' => $rate['id'] ?? null,
                    'name' => $rateName,
                    'description' => trim((string)($rate['description'] ?? '')),
                    'price' => number_format(max(0, (float)($rate['price'] ?? 0)), 2, '.', ''),
                    'currency' => strtoupper(trim((string)($rate['currency'] ?? $currency))) ?: $currency,
                    'capacity' => ($rate['capacity'] ?? '') === '' ? null : max(0, (int)$rate['capacity']),
                    'sort_order' => $rateIndex,
                    'active' => !empty($rate['active']),
                ];
            }
            if (!$rates) {
                $rates[] = [
                    'name' => __('General'),
                    'price' => '0.00',
                    'currency' => $currency,
                    'capacity' => null,
                    'sort_order' => 0,
                    'active' => true,
                ];
            }
            $types[] = [
                'id' => $type['id'] ?? null,
                'name' => $name,
                'description' => trim((string)($type['description'] ?? '')),
                'capacity' => ($type['capacity'] ?? '') === '' ? null : max(0, (int)$type['capacity']),
                'sort_order' => $typeIndex,
                'active' => !empty($type['active']),
                'ticket_rates' => $rates,
            ];
        }

        if (!$types) {
            $types = $this->defaultTicketCatalog($currency);
        }

        $data['currency'] = $currency;
        $data['ticket_types'] = $types;

        return $data;
    }

    private function defaultTicketCatalog(string $currency = 'MXN'): array
    {
        return [[
            'name' => __('Entrada general'),
            'description' => __('Acceso general al evento.'),
            'capacity' => null,
            'sort_order' => 0,
            'active' => true,
            'ticket_rates' => [[
                'name' => __('General'),
                'description' => __('Tarifa base del evento.'),
                'price' => '0.00',
                'currency' => $currency,
                'capacity' => null,
                'sort_order' => 0,
                'active' => true,
            ]],
        ]];
    }

    private function buildRateCatalog($event): array
    {
        $catalog = [];
        foreach ($event->ticket_types ?? [] as $type) {
            if (!$type->active) {
                continue;
            }
            foreach ($type->ticket_rates ?? [] as $rate) {
                if (!$rate->active) {
                    continue;
                }
                $price = (float)$rate->price;
                $currency = $rate->currency ?: ($event->currency ?: 'MXN');
                $catalog[$rate->id] = [
                    'id' => $rate->id,
                    'ticket_type_id' => $type->id,
                    'ticket_type_name' => $type->name,
                    'name' => $rate->name,
                    'price' => number_format($price, 2, '.', ''),
                    'currency' => $currency,
                    'label' => sprintf('%s - %s (%s %s)', $type->name, $rate->name, $currency, number_format($price, 2)),
                ];
            }
        }

        return $catalog;
    }

    private function resolveRateKey(string $value, array $rateCatalog, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        if (isset($rateCatalog[$value])) {
            return $value;
        }

        $normalized = mb_strtolower($value);
        foreach ($rateCatalog as $id => $rate) {
            $labels = [
                mb_strtolower($rate['label']),
                mb_strtolower($rate['name']),
                mb_strtolower($rate['ticket_type_name'] . ' - ' . $rate['name']),
            ];
            if (in_array($normalized, $labels, true)) {
                return $id;
            }
        }

        throw new \RuntimeException(__('La tarifa "{0}" no existe o no esta activa.', $value));
    }

    private function assertTicketCatalogAvailability($connection, string $eventId, array $ticketData): void
    {
        $typeQuantities = [];
        $rateQuantities = [];
        foreach ($ticketData as $ticket) {
            $typeQuantities[$ticket['ticket_type_id']] = ($typeQuantities[$ticket['ticket_type_id']] ?? 0) + 1;
            $rateQuantities[$ticket['ticket_rate_id']] = ($rateQuantities[$ticket['ticket_rate_id']] ?? 0) + 1;
        }

        foreach ($typeQuantities as $typeId => $quantity) {
            $type = $connection->execute(
                'SELECT id, name, capacity, active FROM ticket_types WHERE id = ? AND event_id = ? FOR UPDATE',
                [$typeId, $eventId]
            )->fetch('assoc');
            if (!$type || !(bool)$type['active']) {
                throw new \RuntimeException(__('Uno de los tipos de boleto no esta disponible.'));
            }
            if ($type['capacity'] !== null) {
                $sold = (int)$connection->execute(
                    'SELECT COUNT(*) AS total FROM tickets WHERE event_id = ? AND ticket_type_id = ? AND active = 1',
                    [$eventId, $typeId]
                )->fetch('assoc')['total'];
                $remaining = max(0, (int)$type['capacity'] - $sold);
                if ($quantity > $remaining) {
                    throw new \RuntimeException(__('No hay cupo suficiente para {0}. Disponibles: {1}.', $type['name'], $remaining));
                }
            }
        }

        foreach ($rateQuantities as $rateId => $quantity) {
            $rate = $connection->execute(
                'SELECT ticket_rates.id, ticket_rates.name, ticket_rates.capacity, ticket_rates.active, ticket_types.event_id
                 FROM ticket_rates
                 INNER JOIN ticket_types ON ticket_types.id = ticket_rates.ticket_type_id
                 WHERE ticket_rates.id = ? AND ticket_types.event_id = ? FOR UPDATE',
                [$rateId, $eventId]
            )->fetch('assoc');
            if (!$rate || !(bool)$rate['active']) {
                throw new \RuntimeException(__('Una de las tarifas no esta disponible.'));
            }
            if ($rate['capacity'] !== null) {
                $sold = (int)$connection->execute(
                    'SELECT COUNT(*) AS total FROM tickets WHERE event_id = ? AND ticket_rate_id = ? AND active = 1',
                    [$eventId, $rateId]
                )->fetch('assoc')['total'];
                $remaining = max(0, (int)$rate['capacity'] - $sold);
                if ($quantity > $remaining) {
                    throw new \RuntimeException(__('No hay cupo suficiente para la tarifa {0}. Disponibles: {1}.', $rate['name'], $remaining));
                }
            }
        }
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
