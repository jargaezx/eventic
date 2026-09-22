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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Service\EventCoverRenderer;
use App\Service\TicketRenderer;
use App\Model\Entity\Staff;
use App\Utility\EventDefaults;
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
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
            'Owners',
            'CreatedByUsers',
            'ModifiedByUsers',
            'Staffs' => fn ($query) => $query
                ->contain([
                    'Users',
                    'StaffTicketTypeLimits' => fn ($limitQuery) => $limitQuery
                        ->contain(['TicketTypes'])
                        ->where(['StaffTicketTypeLimits.active' => true]),
                ])
                ->where(['Staffs.active' => true])
                ->orderBy(['Staffs.role' => 'ASC', 'Users.names' => 'ASC']),
        ]);

        $this->Authorization->authorize($event, 'view');
        $emailQueue = $this->fetchTable('EmailJobs')->operationalStatus((string)$event->id);
        $canRetryEmailJobs = $this->request->getAttribute('identity')->can('manageTickets', $event);
        $uncertainEmails = $canRetryEmailJobs ? $this->fetchTable('EmailJobs')->find()
            ->where(['event_id' => $event->id, 'status' => 'uncertain'])
            ->orderBy(['modified' => 'ASC'])->limit(20)->all() : [];

        $ticketPreview = null;
        try {
            $ticketPreview = (new TicketRenderer())->renderPreview($event);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            $this->Flash->warning(__('La vista previa del pase no está disponible.'));
        }

        $this->set(compact('event', 'ticketPreview', 'emailQueue', 'canRetryEmailJobs', 'uncertainEmails'));
    }

    public function resolveEmailDelivery($id = null, $jobId = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'manageTickets');
        if ($this->request->getData('verified') !== '1') {
            $this->Flash->error(__('Confirma que revisaste la entrega antes de resolver este correo.'));
        } else {
            try {
                $this->fetchTable('EmailJobs')->resolveUncertain(
                    (string)$event->id, (string)$jobId, (string)$this->request->getData('decision')
                );
                $this->Flash->success(__('La entrega quedó resuelta.'));
            } catch (\Throwable $exception) {
                $this->log($exception->getMessage(), 'error');
                $this->Flash->error(__('No se pudo resolver el correo. Actualiza el estado y revisa que el pase siga activo y no tenga otro envío.'));
            }
        }

        return $this->redirect(['action' => 'view', $event->id, '#' => 'email-queue']);
    }

    public function retryFailedEmails($id = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'manageTickets');

        try {
            $result = $this->fetchTable('EmailJobs')->retryFailedForEvent((string)$event->id);
            if ($result['retried'] > 0) {
                $this->Flash->success(__('{0} correos fallidos volvieron a la cola de envío.', $result['retried']));
            } else {
                $this->Flash->info(__('No hay correos fallidos que se puedan reintentar.'));
            }
            if ($result['skipped'] > 0) {
                $this->Flash->warning(__('{0} correos se omitieron: no están disponibles para reintento o ya tienen otro envío en cola o procesado.', $result['skipped']));
            }
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            $this->Flash->error(__('No fue posible reintentar los correos. Intenta nuevamente.'));
        }

        return $this->redirect(['action' => 'view', $event->id, '#' => 'email-queue']);
    }

    public function add()
    {
        $event = $this->Events->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->normalizeTicketCatalogData($this->request->getData());
            try {
                $this->assertTicketTypeConfigurationIsSafe($event->id ?? null, $data);
            } catch (\RuntimeException $exception) {
                $this->Flash->error($exception->getMessage());
                $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes']]);
                $this->setFormLists($event);
                return;
            }
            $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes']]);
            $event->owner_id = $this->Authentication->getIdentity()->id;
            $event->created_by = $this->Authentication->getIdentity()->id;
            $event->modified_by = $this->Authentication->getIdentity()->id;
            if ($this->Events->save($event)) {
                (new EventCoverRenderer())->ensure($event, $this->Events);
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
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->normalizeTicketCatalogData($this->request->getData());
            try {
                $this->assertTicketTypeConfigurationIsSafe($event->id, $data);
            } catch (\RuntimeException $exception) {
                $this->Flash->error($exception->getMessage());
                $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes']]);
                $this->setFormLists($event);
                return;
            }
            $event = $this->Events->patchEntity($event, $data, ['associated' => ['TicketConfigurations', 'TicketTypes']]);
            $event->modified_by = $this->Authentication->getIdentity()->id;

            if ($this->Events->save($event)) {
                (new EventCoverRenderer())->ensure($event, $this->Events);
                $this->Flash->success(__('El evento ha sido editado correctamente.'));
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('El evento no pudo ser editado. Por favor, intenta de nuevo.'));
        }
        $this->attachTicketTypeUsageCounts($event);
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
        if (!$event->ticket_configuration) {
            $event->ticket_configuration = $this->Events->TicketConfigurations->newEntity([
                'event_id' => $event->id,
                'x' => 930,
                'y' => 210,
                'qr_size' => 240,
                'active' => true,
            ]);
        }

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

        $editorTemplate = (new TicketRenderer())->renderEditorTemplate($event);
        $this->set(compact('event', 'editorTemplate') + ['ticketConfiguration' => $event->ticket_configuration]);
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
            ->contain(['TicketTypes', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
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
        $this->Authorization->authorize($event, 'view');
        $ticket = $this->Events->Tickets->find()
            ->contain(['Events', 'TicketTypes', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
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
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'register');
        $typeCatalog = $this->buildTicketTypeCatalog($event);
        if (!$typeCatalog) {
            $this->Flash->warning(__('Configura al menos un tipo de boleto activo antes de emitir pases.'));
            return $this->redirect(['action' => 'edit', $event->id]);
        }
        $firstTypeId = array_key_first($typeCatalog);
        $n = $this->request->getQuery('n', 1);
        $tickets = [];
        for ($i = 0; $i < max(1, (int)$n); $i++) {
            $tickets[] = [
                'name' => '',
                'email' => '',
                'ticket_type_id' => $firstTypeId,
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
                        'ticket_type_id' => $this->resolveTicketTypeKey((string)($row[2] ?? ''), $typeCatalog, $firstTypeId),
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
                    $typeCatalog
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
                    $this->Flash->success(__('El registro ha sido procesado correctamente. Los correos quedaron en cola de envío.'));
                    return $this->redirect(['action' => 'register', $id]);
                } catch (\Throwable $exception) {
                    $this->Flash->error($exception->getMessage());
                }
            }
        }

        $batchTotal = array_sum(array_map(fn ($ticket) => (float)($ticket['price'] ?? 0), $tickets));
        if (!$batchTotal) {
            $batchTotal = array_sum(array_map(fn ($ticket) => (float)($typeCatalog[$ticket['ticket_type_id']]['price'] ?? 0), $tickets));
        }
        $typeOptions = [];
        $typeMeta = [];
        foreach ($typeCatalog as $typeId => $type) {
            $typeOptions[$typeId] = $type['label'];
            $typeMeta[$typeId] = [
                'price' => (float)$type['price'],
                'currency' => $type['currency'],
                'isFree' => (float)$type['price'] <= 0,
            ];
        }

        $this->set(compact('event', 'tickets', 'batchTotal', 'typeOptions', 'typeMeta'));
    }

    public function downloadBulkTemplate($id)
    {
        $event = $this->Events->get($id, contain: [
            'TicketTypes' => fn ($query) => $query
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'register');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('Formato pases'));
        $typeCatalog = $this->buildTicketTypeCatalog($event);
        $sampleType = $typeCatalog ? reset($typeCatalog)['label'] : __('Entrada general');
        $sheet->fromArray([
            [__('nombre'), __('correo_entrega'), __('tipo_boleto')],
            [__('Nombre del asistente'), __('comprador@empresa.com'), $sampleType],
        ]);
        $sheet->getStyle('A1:C1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF76132C');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:C200');
        $sheet->getStyle('A:C')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A:C')->getAlignment()->setWrapText(true);
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $catalogSheet = $spreadsheet->createSheet();
        $catalogSheet->setTitle(__('Catálogo'));
        $catalogRows = [[
            __('Tipo de boleto'),
            __('Precio'),
            __('Moneda'),
            __('Capacidad'),
            __('Descripción'),
        ]];
        foreach ($typeCatalog as $type) {
            $catalogRows[] = [
                $type['label'],
                (float)$type['price'],
                $type['currency'],
                $type['capacity'] ?? '',
                $type['description'] ?? '',
            ];
        }
        $catalogSheet->fromArray($catalogRows, null, 'A1', true);
        $this->styleDataSheet($catalogSheet, 1, ['B']);

        $helpSheet = $spreadsheet->createSheet();
        $helpSheet->setTitle(__('Ayuda'));
        $helpSheet->fromArray([
            [__('Campo'), __('Uso')],
            [__('nombre'), __('Nombre completo de la persona que usará el pase. Obligatorio.')],
            [__('correo_entrega'), __('Correo donde se recibirá el pase digital. Obligatorio.')],
            [__('tipo_boleto'), __('Debe coincidir con una opción de la hoja Catálogo.')],
            [__('pago'), __('El sistema marca el pase como pagado cuando el tipo de boleto tiene costo, o gratis cuando su precio es 0.')],
        ], null, 'A1', true);
        $this->styleDataSheet($helpSheet, 1);

        if ($typeCatalog) {
            $typeCount = count($typeCatalog) + 1;
            for ($row = 2; $row <= 200; $row++) {
                $typeValidation = $sheet->getCell("C{$row}")->getDataValidation();
                $typeValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $typeValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $typeValidation->setAllowBlank(false);
                $typeValidation->setShowDropDown(true);
                $typeValidation->setFormula1("'Catálogo'!\$A\$2:\$A\$" . $typeCount);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'formato-carga-pases-' . Text::slug(strtolower((string)$event->name)) . '.xlsx';
        $path = TMP . $filename;
        $writer->save($path);

        return $this->response->withFile($path, ['download' => true, 'name' => $filename]);
    }

    public function addStaff($id)
    {
        $event = $this->Events->get($id, contain: [
            'TicketTypes' => fn ($query) => $query
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event);
        $staffsTable = $this->fetchTable('Staffs');
        $staffTypeLimitsTable = $this->fetchTable('StaffTicketTypeLimits');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $usersData = array_filter($this->request->getData('users', []), function($value){
                return !empty($value['id']);
            });

            try {
                $staffsTable->getConnection()->transactional(function () use ($staffsTable, $staffTypeLimitsTable, $event, $usersData): void {
                    $selectedUserIds = [];
                    foreach ($usersData as $user) {
                        $joinData = $user['_joinData'] ?? $user['_join_data'] ?? [];
                        $role = Staff::normalizeRole($joinData['role'] ?? null);
                        $defaults = Staff::roleDefaults($role);
                        $customMode = !empty($joinData['custom_permissions']);

                        $joinData['role'] = $role;
                        $joinData['role_label'] = Staff::roleOptions()[$role] ?? null;
                        $typeLimits = (array)($joinData['ticket_type_limits'] ?? []);
                        foreach ($defaults as $permission => $value) {
                            $joinData[$permission] = $customMode ? !empty($joinData[$permission]) : $value;
                        }
                        $joinData['register'] = !empty($joinData['can_register']);
                        $joinData['scan'] = !empty($joinData['can_scan']);
                        $salesLimit = ($joinData['sales_limit'] ?? '') === '' ? null : max(0, (int)$joinData['sales_limit']);
                        $joinData['sales_limit'] = $salesLimit;
                        if (empty($joinData['can_register'])) {
                            $salesLimit = null;
                            $joinData['sales_limit'] = null;
                            $typeLimits = [];
                        }
                        $soldByUser = (int)$this->Events->Tickets->find()
                            ->where([
                                'Tickets.event_id' => $event->id,
                                'Tickets.registered_by' => $user['id'],
                                'Tickets.active' => true,
                            ])
                            ->count();
                        if ($salesLimit !== null && $salesLimit < $soldByUser) {
                            throw new \RuntimeException(__('El límite global de venta no puede ser menor a los pases ya emitidos por este usuario ({0}).', $soldByUser));
                        }
                        $joinData['sales_count'] = $soldByUser;
                        $joinData['active'] = true;
                        unset($joinData['custom_permissions'], $joinData['ticket_type_limits']);

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
                        $this->saveStaffTicketTypeLimits($staffTypeLimitsTable, $staff, $event, $typeLimits, $salesLimit);
                    }

                    $conditions = ['event_id' => $event->id];
                    if ($selectedUserIds) {
                        $conditions['user_id NOT IN'] = $selectedUserIds;
                    }
                    $staffsTable->updateAll(['active' => false], $conditions);
                    $this->assertStaffTypeLimitsWithinCapacity($event->id);
                });

                $this->Flash->success(__('El equipo del evento ha sido actualizado correctamente.'));
                return $this->redirect(['action' => 'addStaff', $id]);
            } catch (\RuntimeException $exception) {
                $this->Flash->error($exception->getMessage());
            } catch (\Cake\ORM\Exception\PersistenceFailedException $exception) {
                $errors = $exception->getEntity()->getErrors();
                $message = $this->firstValidationError($errors) ?: __('Revisa los datos del usuario y sus permisos.');
                $this->Flash->error(__('El personal del evento no pudo guardarse: {0}', $message));
            } catch (\Throwable $exception) {
                $this->log($exception->getMessage(), 'error');
                $this->Flash->error(__('El personal del evento no pudo guardarse. Revisa que el usuario seleccionado tenga rol y permisos válidos.'));
            }
        }
        $users = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $staffByUser = $staffsTable->find()
            ->contain(['StaffTicketTypeLimits'])
            ->where([
                'event_id' => $event->id,
                'active' => true,
            ])
            ->all()
            ->combine('user_id', fn ($staff) => $staff)
            ->toArray();
        $roleOptions = Staff::roleOptions();
        $roleDescriptions = Staff::roleDescriptions();
        $ticketTypes = $event->ticket_types ?? [];
        $this->set(compact('event', 'users', 'staffByUser', 'roleOptions', 'roleDescriptions', 'ticketTypes'));
    }

    public function saveStaffMember($id = null, $userId = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $event = $this->Events->get($id, contain: [
            'TicketTypes' => fn ($query) => $query
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'addStaff');

        $staffsTable = $this->fetchTable('Staffs');
        $staffTypeLimitsTable = $this->fetchTable('StaffTicketTypeLimits');
        $usersData = array_filter($this->request->getData('users', []), fn ($value) => !empty($value['id']));
        $userData = array_values($usersData)[0] ?? ['id' => $userId, '_joinData' => $this->request->getData()];
        $targetUserId = (string)($userData['id'] ?? $userId);

        if (!$targetUserId || (string)$userId !== $targetUserId) {
            return $this->jsonResponse(['ok' => false, 'message' => __('No se pudo identificar al usuario.')], 400);
        }

        try {
            $staff = null;
            $staffsTable->getConnection()->transactional(function () use ($staffsTable, $staffTypeLimitsTable, $event, $userData, $targetUserId, &$staff): void {
                $joinData = $userData['_joinData'] ?? $userData['_join_data'] ?? [];
                $role = Staff::normalizeRole($joinData['role'] ?? null);
                $defaults = Staff::roleDefaults($role);
                $customMode = !empty($joinData['custom_permissions']);

                $joinData['role'] = $role;
                $joinData['role_label'] = Staff::roleOptions()[$role] ?? null;
                $typeLimits = (array)($joinData['ticket_type_limits'] ?? []);
                foreach ($defaults as $permission => $value) {
                    $joinData[$permission] = $customMode ? !empty($joinData[$permission]) : $value;
                }
                $joinData['register'] = !empty($joinData['can_register']);
                $joinData['scan'] = !empty($joinData['can_scan']);
                $salesLimit = ($joinData['sales_limit'] ?? '') === '' ? null : max(0, (int)$joinData['sales_limit']);
                $joinData['sales_limit'] = $salesLimit;
                if (empty($joinData['can_register'])) {
                    $salesLimit = null;
                    $joinData['sales_limit'] = null;
                    $typeLimits = [];
                }

                $soldByUser = (int)$this->Events->Tickets->find()
                    ->where([
                        'Tickets.event_id' => $event->id,
                        'Tickets.registered_by' => $targetUserId,
                        'Tickets.active' => true,
                    ])
                    ->count();
                if ($salesLimit !== null && $salesLimit < $soldByUser) {
                    throw new \RuntimeException(__('El límite global de venta no puede ser menor a los pases ya emitidos por este usuario ({0}).', $soldByUser));
                }
                $joinData['sales_count'] = $soldByUser;
                $joinData['active'] = true;
                unset($joinData['custom_permissions'], $joinData['ticket_type_limits']);

                $staff = $staffsTable->find()
                    ->where([
                        'event_id' => $event->id,
                        'user_id' => $targetUserId,
                    ])
                    ->first() ?: $staffsTable->newEmptyEntity();

                $staff = $staffsTable->patchEntity($staff, $joinData + [
                    'event_id' => $event->id,
                    'user_id' => $targetUserId,
                ]);
                $staffsTable->saveOrFail($staff);
                $this->saveStaffTicketTypeLimits($staffTypeLimitsTable, $staff, $event, $typeLimits, $salesLimit);
                $this->assertStaffTypeLimitsWithinCapacity($event->id);
            });

            $staff = $staffsTable->get($staff->id, contain: ['StaffTicketTypeLimits']);

            return $this->jsonResponse([
                'ok' => true,
                'message' => __('Integrante actualizado.'),
                'staff' => $this->staffPayload($staff),
            ]);
        } catch (\RuntimeException $exception) {
            return $this->jsonResponse(['ok' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Cake\ORM\Exception\PersistenceFailedException $exception) {
            $message = $this->firstValidationError($exception->getEntity()->getErrors()) ?: __('Revisa los datos del usuario y sus permisos.');

            return $this->jsonResponse(['ok' => false, 'message' => $message], 422);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');

            return $this->jsonResponse(['ok' => false, 'message' => __('No fue posible actualizar el integrante.')], 500);
        }
    }

    public function removeStaffMember($id = null, $userId = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'addStaff');

        $staffsTable = $this->fetchTable('Staffs');
        $staffTypeLimitsTable = $this->fetchTable('StaffTicketTypeLimits');

        try {
            $staffsTable->getConnection()->transactional(function () use ($staffsTable, $staffTypeLimitsTable, $event, $userId): void {
                $staff = $staffsTable->find()
                    ->where([
                        'event_id' => $event->id,
                        'user_id' => $userId,
                        'active' => true,
                    ])
                    ->first();

                if (!$staff) {
                    return;
                }

                $staff = $staffsTable->patchEntity($staff, [
                    'active' => false,
                    'sales_limit' => null,
                ]);
                $staffsTable->saveOrFail($staff);
                $staffTypeLimitsTable->updateAll(['active' => false], ['staff_id' => $staff->id]);
                $this->assertStaffTypeLimitsWithinCapacity($event->id);
            });

            return $this->jsonResponse([
                'ok' => true,
                'message' => __('Integrante retirado.'),
            ]);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');

            return $this->jsonResponse(['ok' => false, 'message' => __('No fue posible retirar el integrante.')], 500);
        }
    }

    public function scan($id = null){
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);
        $this->set(compact('event'));
    }

    public function report($id = null){
        $event = $this->Events->get($id, contain: ['Users', 'Owners']);
        $this->Authorization->authorize($event, 'report');
        $filters = $this->ticketReportFilters('active');
        $query = $this->buildTicketReportQuery($event, $filters)
            ->orderBy(['Tickets.created' => 'DESC']);

        $tickets = $this->paginate(
            $query,
            ['limit' => 75]
        );

        $ticketTypes = $this->Events->TicketTypes->find('list')
            ->where(['event_id' => $event->id, 'active' => true])
            ->orderBy(['sort_order' => 'ASC', 'name' => 'ASC'])
            ->all();

        $this->set(compact('event', 'tickets', 'filters', 'ticketTypes'));
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
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => false, 'message' => __('No es posible reenviar un pase cancelado.')], 400);
            }
            $this->Flash->warning(__('No es posible reenviar un pase cancelado.'));
            return $this->redirect(['action' => 'register', $event->id]);
        }

        $email = trim((string)$this->request->getData('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => false, 'message' => __('Ingresa un correo electrónico válido para reenviar el pase.')], 400);
            }
            $this->Flash->error(__('Ingresa un correo electrónico válido para reenviar el pase.'));
            return $this->redirect(['action' => 'register', $event->id]);
        }

        if ($email !== $ticket->email) {
            $ticket = $this->Events->Tickets->patchEntity($ticket, ['email' => $email]);
            if (!$this->Events->Tickets->save($ticket)) {
                if ($this->wantsJsonResponse()) {
                    return $this->jsonResponse(['ok' => false, 'message' => __('El correo del pase no pudo ser actualizado.')], 422);
                }
                $this->Flash->error(__('El correo del pase no pudo ser actualizado.'));
                return $this->redirect(['action' => 'register', $event->id]);
            }
        }

        try {
            $this->Events->Tickets->queueTicketEmail($ticket, $email);
            $message = __('El pase quedó en cola para enviarse a {0}.', $email);
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse([
                    'ok' => true,
                    'message' => $message,
                    'ticket' => $this->ticketDeliveryPayload($ticket),
                    'queued' => true,
                ]);
            }
            $this->Flash->success($message);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => false, 'message' => __('No fue posible poner el pase en cola de envío. Intenta nuevamente.')], 500);
            }
            $this->Flash->error(__('No fue posible poner el pase en cola de envío. Intenta nuevamente.'));
        }

        return $this->redirect(['action' => 'register', $event->id]);
    }

    public function sendTestTicket($id = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id, contain: [
            'TicketConfigurations',
            'TicketTypes' => fn ($query) => $query
                ->where(['TicketTypes.active' => true])
                ->orderBy(['TicketTypes.sort_order' => 'ASC', 'TicketTypes.name' => 'ASC']),
        ]);
        $this->Authorization->authorize($event, 'edit');

        $email = trim((string)$this->request->getData('test_email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = __('Ingresa un correo electrónico válido.');
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => false, 'message' => $message], 400);
            }

            $this->Flash->error($message);

            return $this->redirect(['action' => 'view', $event->id]);
        }

        try {
            $this->Events->Tickets->deliverTestTicketEmail($event, $email);
            $message = __('Prueba enviada.');
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => true, 'message' => $message]);
            }

            $this->Flash->success($message);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), 'error');
            $message = __('No fue posible enviar la prueba. Revisa la configuración de correo.');
            if ($this->wantsJsonResponse()) {
                return $this->jsonResponse(['ok' => false, 'message' => $message], 500);
            }

            $this->Flash->error($message);
        }

        return $this->redirect(['action' => 'view', $event->id]);
    }

    public function cancelTicket($id = null, $ticketId = null)
    {
        $this->request->allowMethod(['post']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'cancelTickets');

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
                    throw new \RuntimeException(__('Este pase ya está cancelado.'));
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
                    if ($ticket->ticket_type_id) {
                        $connection->execute(
                            'UPDATE staff_ticket_type_limits sttl
                             INNER JOIN staffs s ON s.id = sttl.staff_id
                             SET sttl.sales_count = GREATEST(sttl.sales_count - 1, 0)
                             WHERE sttl.event_id = ?
                               AND sttl.ticket_type_id = ?
                               AND s.user_id = ?',
                            [$event->id, $ticket->ticket_type_id, $ticket->registered_by]
                        );
                    }
                }

                $folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
            });

            $this->Flash->success(__('El pase {0} fue cancelado y el cupo quedó disponible.', $folio));
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
        $tickets = $this->buildTicketReportQuery($event, $this->ticketReportFilters('all'), ['TicketTypes', 'RegisteredByUsers'])
            ->orderBy(['Tickets.created' => 'ASC', 'Tickets.folio' => 'ASC'])
            ->all()
            ->toList();

        $summary = [];
        $summaryByType = [];
        $activeTickets = 0;
        $cancelledTickets = 0;
        $attendedTickets = 0;
        $netTotal = 0.0;
        $cancelledTotal = 0.0;

        foreach ($tickets as $ticket) {
            $seller = $ticket->registered_by_user->full_name ?? __('Sin responsable');
            $type = $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? __('Sin tipo'));
            $key = implode('|', [$seller, $type]);
            $summaryByType[$type] ??= [
                'type' => $type,
                'active' => 0,
                'cancelled' => 0,
                'attended' => 0,
                'amount' => 0.0,
                'cancelled_amount' => 0.0,
            ];
            $summary[$key] ??= [
                'seller' => $seller,
                'type' => $type,
                'active' => 0,
                'cancelled' => 0,
                'attended' => 0,
                'amount' => 0.0,
                'cancelled_amount' => 0.0,
            ];

            $price = (float)$ticket->price;
            if ($ticket->active) {
                $activeTickets++;
                $summary[$key]['active']++;
                $summaryByType[$type]['active']++;
                $summary[$key]['amount'] += $price;
                $summaryByType[$type]['amount'] += $price;
                $netTotal += $price;
                if ($ticket->attended) {
                    $attendedTickets++;
                    $summary[$key]['attended']++;
                    $summaryByType[$type]['attended']++;
                }
            } else {
                $cancelledTickets++;
                $summary[$key]['cancelled']++;
                $summaryByType[$type]['cancelled']++;
                $summary[$key]['cancelled_amount'] += $price;
                $summaryByType[$type]['cancelled_amount'] += $price;
                $cancelledTotal += $price;
            }
        }
        ksort($summary);
        ksort($summaryByType);

        $spreadsheet = new Spreadsheet();
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle(__('Resumen'));
        $this->buildReportCover($summarySheet, $event, __('Balance de ventas'), [
            __('Pases activos') => $activeTickets,
            __('Pases cancelados') => $cancelledTickets,
            __('Asistencias') => $attendedTickets,
            __('Pendientes de asistencia') => max(0, $activeTickets - $attendedTickets),
            __('Monto activo') => $netTotal,
            __('Monto cancelado') => $cancelledTotal,
            __('Monto emitido') => $netTotal + $cancelledTotal,
        ], ['money' => [__('Monto activo'), __('Monto cancelado'), __('Monto emitido')]]);

        $balanceSheet = $spreadsheet->createSheet();
        $balanceSheet->setTitle(__('Por vendedor'));
        $balanceRows = [[
            __('Responsable'),
            __('Tipo de boleto'),
            __('Boletos activos'),
            __('Cancelados'),
            __('Asistencias'),
            __('Pendientes'),
            __('Monto activo'),
            __('Monto cancelado'),
            __('Monto emitido'),
        ]];
        foreach ($summary as $totals) {
            $balanceRows[] = [
                $totals['seller'],
                $totals['type'],
                $totals['active'],
                $totals['cancelled'],
                $totals['attended'],
                max(0, $totals['active'] - $totals['attended']),
                $totals['amount'],
                $totals['cancelled_amount'],
                $totals['amount'] + $totals['cancelled_amount'],
            ];
        }
        $balanceRows[] = [
            __('Total'),
            '',
            $activeTickets,
            $cancelledTickets,
            $attendedTickets,
            max(0, $activeTickets - $attendedTickets),
            $netTotal,
            $cancelledTotal,
            $netTotal + $cancelledTotal,
        ];
        $balanceSheet->fromArray($balanceRows, null, 'A1', true);
        $this->styleDataSheet($balanceSheet, 1, ['G', 'H', 'I']);

        $typeSheet = $spreadsheet->createSheet();
        $typeSheet->setTitle(__('Por tipo'));
        $typeRows = [[
            __('Tipo de boleto'),
            __('Boletos activos'),
            __('Cancelados'),
            __('Asistencias'),
            __('Pendientes'),
            __('Monto activo'),
            __('Monto cancelado'),
            __('Monto emitido'),
        ]];
        foreach ($summaryByType as $totals) {
            $typeRows[] = [
                $totals['type'],
                $totals['active'],
                $totals['cancelled'],
                $totals['attended'],
                max(0, $totals['active'] - $totals['attended']),
                $totals['amount'],
                $totals['cancelled_amount'],
                $totals['amount'] + $totals['cancelled_amount'],
            ];
        }
        $typeRows[] = [
            __('Total'),
            $activeTickets,
            $cancelledTickets,
            $attendedTickets,
            max(0, $activeTickets - $attendedTickets),
            $netTotal,
            $cancelledTotal,
            $netTotal + $cancelledTotal,
        ];
        $typeSheet->fromArray($typeRows, null, 'A1', true);
        $this->styleDataSheet($typeSheet, 1, ['F', 'G', 'H']);

        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle(__('Detalle'));
        $detailRows = [[
            __('Folio'),
            __('Asistente'),
            __('Correo'),
            __('Tipo de boleto'),
            __('Importe'),
            __('Moneda'),
            __('Registrado por'),
            __('Fecha de emisión'),
            __('Asistencia'),
            __('Estado'),
            __('Cancelado'),
            __('Motivo de cancelación'),
        ]];
        foreach ($tickets as $ticket) {
            $detailRows[] = [
                str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT),
                $ticket->name,
                $ticket->email,
                $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? ''),
                (float)$ticket->price,
                $ticket->currency ?: ($event->currency ?: 'MXN'),
                $ticket->registered_by_user->full_name ?? '',
                $this->formatReportDate($ticket->created),
                $this->formatReportDate($ticket->attended),
                $ticket->active ? __('Activo') : __('Cancelado'),
                $this->formatReportDate($ticket->cancelled),
                $ticket->cancelled_reason ?: '',
            ];
        }
        $detailSheet->fromArray($detailRows, null, 'A1', true);
        $this->styleDataSheet($detailSheet, 1, ['E']);

        return $this->downloadSpreadsheet($event, __('balance-ventas'), $spreadsheet);
    }

    public function exportAttendance($id = null)
    {
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event, 'report');
        $tickets = $this->buildTicketReportQuery($event, $this->ticketReportFilters('all'), ['TicketTypes', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
            ->orderBy(['Tickets.folio' => 'ASC'])
            ->all()
            ->toList();

        $activeTickets = 0;
        $cancelledTickets = 0;
        $attendedTickets = 0;
        $byType = [];

        foreach ($tickets as $ticket) {
            $type = $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? __('Sin tipo'));
            $byType[$type] ??= [
                'type' => $type,
                'active' => 0,
                'cancelled' => 0,
                'attended' => 0,
            ];

            if ($ticket->active) {
                $activeTickets++;
                $byType[$type]['active']++;
                if ($ticket->attended) {
                    $attendedTickets++;
                    $byType[$type]['attended']++;
                }
            } else {
                $cancelledTickets++;
                $byType[$type]['cancelled']++;
            }
        }
        ksort($byType);

        $capacity = max(1, (int)$event->capacity);
        $spreadsheet = new Spreadsheet();
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle(__('Resumen'));
        $this->buildReportCover($summarySheet, $event, __('Reporte de asistencia'), [
            __('Capacidad') => (int)$event->capacity,
            __('Pases activos') => $activeTickets,
            __('Asistencias') => $attendedTickets,
            __('Pendientes') => max(0, $activeTickets - $attendedTickets),
            __('Cancelados') => $cancelledTickets,
            __('Ocupación') => $activeTickets / $capacity,
            __('Check-in') => $activeTickets > 0 ? $attendedTickets / $activeTickets : 0,
        ], ['percent' => [__('Ocupación'), __('Check-in')]]);

        $typeSheet = $spreadsheet->createSheet();
        $typeSheet->setTitle(__('Por tipo'));
        $typeRows = [[
            __('Tipo de boleto'),
            __('Pases activos'),
            __('Asistencias'),
            __('Pendientes'),
            __('Cancelados'),
            __('Check-in'),
        ]];
        foreach ($byType as $typeTotals) {
            $typeRows[] = [
                $typeTotals['type'],
                $typeTotals['active'],
                $typeTotals['attended'],
                max(0, $typeTotals['active'] - $typeTotals['attended']),
                $typeTotals['cancelled'],
                $typeTotals['active'] > 0 ? $typeTotals['attended'] / $typeTotals['active'] : 0,
            ];
        }
        $typeRows[] = [
            __('Total'),
            $activeTickets,
            $attendedTickets,
            max(0, $activeTickets - $attendedTickets),
            $cancelledTickets,
            $activeTickets > 0 ? $attendedTickets / $activeTickets : 0,
        ];
        $typeSheet->fromArray($typeRows, null, 'A1', true);
        $this->styleDataSheet($typeSheet, 1, [], ['F']);

        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle(__('Detalle'));
        $detailRows = [[
            __('Folio'),
            __('Nombre'),
            __('Correo'),
            __('Tipo de boleto'),
            __('Importe'),
            __('Registrado por'),
            __('Fecha de emisión'),
            __('Asistencia'),
            __('Escaneado por'),
            __('Estado'),
            __('Último correo'),
            __('Cancelado'),
            __('Cancelado por'),
        ]];
        foreach ($tickets as $ticket) {
            $detailRows[] = [
                str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT),
                $ticket->name,
                $ticket->email,
                $ticket->ticket_type_name ?: ($ticket->ticket_type->name ?? ''),
                (float)$ticket->price,
                $ticket->registered_by_user->full_name ?? '',
                $this->formatReportDate($ticket->created),
                $this->formatReportDate($ticket->attended),
                $ticket->checked_in_user->full_name ?? '',
                $ticket->active ? __('Activo') : __('Cancelado'),
                $this->formatReportDate($ticket->last_emailed),
                $this->formatReportDate($ticket->cancelled),
                $ticket->cancelled_by_user->full_name ?? '',
            ];
        }
        $detailSheet->fromArray($detailRows, null, 'A1', true);
        $this->styleDataSheet($detailSheet, 1, ['E']);

        return $this->downloadSpreadsheet($event, __('asistencia'), $spreadsheet);
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
                throw new \RuntimeException(__('El evento no está disponible.'));
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
                    throw new \RuntimeException(__('El límite de venta de este usuario permite emitir {0} pases más.', $remaining));
                }
            }

            $this->assertTicketCatalogAvailability($connection, $eventId, $ticketData);
            if (!$isPrivilegedUser && $staff) {
                $this->assertStaffTicketTypeAvailability($connection, $eventId, $staff['id'], $ticketData);
            }

            $tickets = $this->Events->Tickets->newEntities($ticketData);
            $this->Events->Tickets->saveManyOrFail($tickets);

            if ($staff) {
                $connection->execute(
                    'UPDATE staffs SET sales_count = sales_count + ? WHERE id = ?',
                    [$quantity, $staff['id']]
                );
                foreach ($this->ticketTypeQuantities($ticketData) as $typeId => $typeQuantity) {
                    $connection->execute(
                        'UPDATE staff_ticket_type_limits
                         SET sales_count = sales_count + ?
                         WHERE staff_id = ? AND ticket_type_id = ? AND active = 1',
                        [$typeQuantity, $staff['id'], $typeId]
                    );
                }
            }
        });
    }

    private function ticketReportFilters(string $defaultStatus = 'active'): array
    {
        return [
            'q' => trim((string)$this->request->getQuery('q')),
            'status' => (string)$this->request->getQuery('status', $defaultStatus),
            'attendance' => (string)$this->request->getQuery('attendance', 'all'),
            'delivery' => (string)$this->request->getQuery('delivery', 'all'),
            'type' => (string)$this->request->getQuery('type', 'all'),
        ];
    }

    private function buildTicketReportQuery($event, array $filters, array $contain = ['TicketTypes', 'RegisteredByUsers', 'CheckedInUsers', 'CancelledByUsers'])
    {
        $query = $this->Events->Tickets->find()
            ->contain($contain)
            ->where(['Tickets.event_id' => $event->id]);

        if ($filters['q'] !== '') {
            $query->where(function ($exp) use ($filters) {
                $q = $filters['q'];
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

        if ($filters['status'] === 'active') {
            $query->where(['Tickets.active' => true]);
        } elseif ($filters['status'] === 'cancelled') {
            $query->where(['Tickets.active' => false]);
        }

        if ($filters['attendance'] === 'checked') {
            $query->where(['Tickets.attended IS NOT' => null]);
        } elseif ($filters['attendance'] === 'pending') {
            $query->where(['Tickets.attended IS' => null]);
        }

        if ($filters['delivery'] === 'sent') {
            $query->where(['Tickets.last_emailed IS NOT' => null]);
        } elseif ($filters['delivery'] === 'not_sent') {
            $query->where(['Tickets.last_emailed IS' => null]);
        }

        if ($filters['type'] !== 'all') {
            $query->where(['Tickets.ticket_type_id' => $filters['type']]);
        }

        return $query;
    }

    private function wantsJsonResponse(): bool
    {
        return str_contains((string)$this->request->getHeaderLine('Accept'), 'application/json')
            || strtolower((string)$this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    private function jsonResponse(array $data, int $status = 200)
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($data));
    }

    private function ticketDeliveryPayload($ticket): array
    {
        return [
            'email' => (string)$ticket->email,
            'last_emailed' => $ticket->last_emailed ? $ticket->last_emailed->i18nFormat('dd/MM/yy, HH:mm') : null,
            'email_attempt_count' => (int)$ticket->email_attempt_count,
            'attempts_label' => __('{0} envíos', (int)$ticket->email_attempt_count),
        ];
    }

    private function setFormLists($event): void
    {
        $this->attachTicketTypeUsageCounts($event);
        $owners = $this->Events->Owners->find('list',
            keyField: 'id',
            valueField: function ($user) {
                return $user->get('full_name');
            }
        )->all();
        $this->set(compact('event', 'owners'));
    }

    private function attachTicketTypeUsageCounts($event): void
    {
        if (empty($event->id) || empty($event->ticket_types)) {
            return;
        }

        $rows = $this->Events->Tickets->find()
            ->select([
                'ticket_type_id',
                'sold_count' => $this->Events->Tickets->find()->func()->count('*'),
            ])
            ->where([
                'Tickets.event_id' => $event->id,
                'Tickets.active' => true,
                'Tickets.ticket_type_id IS NOT' => null,
            ])
            ->groupBy(['Tickets.ticket_type_id'])
            ->enableHydration(false)
            ->all();

        $usageCounts = [];
        foreach ($rows as $row) {
            $usageCounts[(string)$row['ticket_type_id']] = (int)$row['sold_count'];
        }

        foreach ($event->ticket_types as $ticketType) {
            $ticketType->set('sold_count', $usageCounts[(string)$ticketType->id] ?? 0);
        }
    }

    private function firstValidationError(array $errors): ?string
    {
        foreach ($errors as $error) {
            if (is_array($error)) {
                $message = $this->firstValidationError($error);
                if ($message !== null) {
                    return $message;
                }
                continue;
            }
            if (is_string($error) && $error !== '') {
                return $error;
            }
        }

        return null;
    }

    private function saveStaffTicketTypeLimits($staffTypeLimitsTable, $staff, $event, array $typeLimits, ?int $globalSalesLimit): void
    {
        $activeTypes = [];
        foreach ((array)($event->ticket_types ?? []) as $type) {
            $activeTypes[(string)$type->id] = [
                'name' => (string)$type->name,
                'capacity' => $type->capacity === null ? null : (int)$type->capacity,
            ];
        }

        $assignedTypeTotal = 0;
        foreach ($activeTypes as $typeId => $typeMeta) {
            $rawLimit = $typeLimits[$typeId]['sales_limit'] ?? '';
            if ($rawLimit === '') {
                continue;
            }

            $salesLimit = max(0, (int)$rawLimit);
            $assignedTypeTotal += $salesLimit;
            if ($typeMeta['capacity'] !== null && $salesLimit > $typeMeta['capacity']) {
                throw new \RuntimeException(__('La cuota de {0} para este usuario supera los boletos disponibles del tipo ({1}).', $typeMeta['name'], $typeMeta['capacity']));
            }
        }

        if ($globalSalesLimit !== null && $assignedTypeTotal > $globalSalesLimit) {
            throw new \RuntimeException(__('Las cuotas por tipo suman {0} y superan el límite global de venta ({1}).', $assignedTypeTotal, $globalSalesLimit));
        }

        foreach ($activeTypes as $typeId => $typeMeta) {
            $rawLimit = $typeLimits[$typeId]['sales_limit'] ?? '';
            $salesLimit = $rawLimit === '' ? null : max(0, (int)$rawLimit);
            $soldByStaff = (int)$this->Events->Tickets->find()
                ->where([
                    'Tickets.event_id' => $event->id,
                    'Tickets.ticket_type_id' => $typeId,
                    'Tickets.registered_by' => $staff->user_id,
                    'Tickets.active' => true,
                ])
                ->count();

            if ($salesLimit !== null && $salesLimit < $soldByStaff) {
                throw new \RuntimeException(__('El límite por tipo no puede ser menor a los pases ya emitidos por el usuario.'));
            }

            $limit = $staffTypeLimitsTable->find()
                ->where([
                    'staff_id' => $staff->id,
                    'ticket_type_id' => $typeId,
                ])
                ->first() ?: $staffTypeLimitsTable->newEmptyEntity();

            $limit = $staffTypeLimitsTable->patchEntity($limit, [
                'staff_id' => $staff->id,
                'event_id' => $event->id,
                'ticket_type_id' => $typeId,
                'sales_limit' => $salesLimit,
                'sales_count' => $soldByStaff,
                'active' => $salesLimit !== null,
            ]);
            $staffTypeLimitsTable->saveOrFail($limit);
        }
    }

    private function staffPayload(Staff $staff): array
    {
        $limits = [];
        foreach ($staff->staff_ticket_type_limits ?? [] as $limit) {
            if ($limit->active) {
                $limits[(string)$limit->ticket_type_id] = [
                    'sales_limit' => $limit->sales_limit,
                    'sales_count' => (int)$limit->sales_count,
                ];
            }
        }

        return [
            'id' => $staff->id,
            'user_id' => $staff->user_id,
            'role' => $staff->role,
            'role_label' => $staff->role_label ?: (Staff::roleOptions()[$staff->role] ?? __('Staff')),
            'can_manage_event' => (bool)$staff->can_manage_event,
            'can_manage_staff' => (bool)$staff->can_manage_staff,
            'can_register' => (bool)$staff->can_register,
            'can_scan' => (bool)$staff->can_scan,
            'can_view_reports' => (bool)$staff->can_view_reports,
            'sales_limit' => $staff->sales_limit,
            'sales_count' => (int)$staff->sales_count,
            'type_limits' => $limits,
        ];
    }

    private function assertStaffTypeLimitsWithinCapacity(string $eventId): void
    {
        $rows = $this->Events->getConnection()->execute(
            'SELECT tt.name, tt.capacity, COALESCE(SUM(sttl.sales_limit), 0) AS assigned
             FROM ticket_types tt
             LEFT JOIN staff_ticket_type_limits sttl
                ON sttl.ticket_type_id = tt.id
               AND sttl.active = 1
             LEFT JOIN staffs s
                ON s.id = sttl.staff_id
               AND s.active = 1
             WHERE tt.event_id = ?
               AND tt.active = 1
               AND tt.capacity IS NOT NULL
               AND (sttl.id IS NULL OR s.id IS NOT NULL)
             GROUP BY tt.id, tt.name, tt.capacity
             HAVING assigned > tt.capacity',
            [$eventId]
        )->fetchAll('assoc');

        if ($rows) {
            $row = $rows[0];
            throw new \RuntimeException(__('Las cuotas asignadas para {0} superan sus boletos disponibles: {1} de {2}.', $row['name'], (int)$row['assigned'], (int)$row['capacity']));
        }
    }

    private function assertTicketTypeConfigurationIsSafe(?string $eventId, array $data): void
    {
        $eventCapacity = max(0, (int)($data['capacity'] ?? 0));
        $soldByExistingType = [];
        if ($eventId) {
            $sold = (int)$this->Events->Tickets->find()
                ->where([
                    'Tickets.event_id' => $eventId,
                    'Tickets.active' => true,
                ])
                ->count();
            if ($eventCapacity > 0 && $eventCapacity < $sold) {
                throw new \RuntimeException(__('La capacidad del evento no puede ser menor a los pases activos ya emitidos ({0}).', $sold));
            }

            $soldRows = $this->Events->Tickets->find()
                ->select([
                    'ticket_type_id',
                    'sold_count' => $this->Events->Tickets->find()->func()->count('*'),
                ])
                ->where([
                    'Tickets.event_id' => $eventId,
                    'Tickets.active' => true,
                    'Tickets.ticket_type_id IS NOT' => null,
                ])
                ->groupBy(['Tickets.ticket_type_id'])
                ->enableHydration(false)
                ->all();

            foreach ($soldRows as $row) {
                $soldByExistingType[(string)$row['ticket_type_id']] = (int)$row['sold_count'];
            }
        }

        $capacitySum = 0;
        $submittedTypeIds = [];
        foreach ((array)($data['ticket_types'] ?? []) as $type) {
            if (!empty($type['id'])) {
                $submittedTypeIds[(string)$type['id']] = true;
            }
            if (empty($type['active'])) {
                if ($eventId && !empty($type['id'])) {
                    $soldByType = $soldByExistingType[(string)$type['id']] ?? 0;
                    if ($soldByType > 0) {
                        throw new \RuntimeException(__('No puedes desactivar un tipo de boleto con pases activos. Cancela o reasigna esos pases antes de retirarlo.'));
                    }
                }
                continue;
            }

            $typeCapacity = max(0, (int)($type['capacity'] ?? 0));
            if ($typeCapacity <= 0) {
                throw new \RuntimeException(__('Cada tipo de boleto activo debe tener al menos 1 boleto asignado.'));
            }
            $capacitySum += $typeCapacity;
            if ($eventId && !empty($type['id'])) {
                $soldByType = $soldByExistingType[(string)$type['id']] ?? 0;
                if ($typeCapacity < $soldByType) {
                    throw new \RuntimeException(__('La cantidad asignada de {0} no puede ser menor a sus pases activos ({1}).', $type['name'], $soldByType));
                }
            }
        }

        foreach ($soldByExistingType as $typeId => $soldByType) {
            if ($soldByType > 0 && empty($submittedTypeIds[$typeId])) {
                throw new \RuntimeException(__('No puedes eliminar un tipo de boleto con pases activos. Cancela o reasigna esos pases antes de retirarlo.'));
            }
        }

        if ($eventCapacity > 0 && $capacitySum !== $eventCapacity) {
            $message = $capacitySum < $eventCapacity
                ? __('Faltan {0} boletos por asignar a un tipo. La suma debe cubrir la capacidad total del evento ({1}).', $eventCapacity - $capacitySum, $eventCapacity)
                : __('Hay {0} boletos excedidos en los tipos. La suma debe coincidir con la capacidad total del evento ({1}).', $capacitySum - $eventCapacity, $eventCapacity);
            throw new \RuntimeException($message);
        }
    }

    private function prepareTicketRows(array $rows, string $buyerEmail = '', array $typeCatalog = []): array
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
            $typeId = (string)($row['ticket_type_id'] ?? '');
            if ($typeId === '' || !isset($typeCatalog[$typeId])) {
                throw new \RuntimeException(__('Selecciona un tipo de boleto valido para todos los pases.'));
            }
            $type = $typeCatalog[$typeId];
            $price = (float)$type['price'];
            $prepared[] = [
                'name' => $name,
                'email' => $email,
                'ticket_type_id' => $typeId,
                'ticket_rate_id' => null,
                'ticket_type_name' => $type['name'],
                'ticket_rate_name' => null,
                'price' => number_format($price, 2, '.', ''),
                'currency' => $type['currency'],
                'payment_status' => $price <= 0 ? 'free' : 'paid',
            ];
        }

        return $prepared;
    }

    private function normalizeTicketCatalogData(array $data): array
    {
        $data = $this->applyEventDefaults($data);
        $currency = strtoupper(trim((string)($data['currency'] ?? EventDefaults::CURRENCY))) ?: EventDefaults::CURRENCY;
        $types = [];
        foreach (array_values((array)($data['ticket_types'] ?? [])) as $typeIndex => $type) {
            $name = trim((string)($type['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $typeData = [
                'name' => $name,
                'description' => trim((string)($type['description'] ?? '')),
                'price' => number_format(max(0, (float)($type['price'] ?? 0)), 2, '.', ''),
                'currency' => $currency,
                'capacity' => ($type['capacity'] ?? '') === '' ? 0 : max(0, (int)$type['capacity']),
                'sort_order' => $typeIndex,
                'active' => !empty($type['active']),
            ];
            if (!empty($type['id'])) {
                $typeData['id'] = (string)$type['id'];
            }
            $types[] = $typeData;
        }

        if (!$types) {
            $types = $this->defaultTicketCatalog($currency, (int)($data['capacity'] ?? 0));
        }

        $data['currency'] = $currency;
        $data['ticket_types'] = $types;

        return $data;
    }

    private function applyEventDefaults(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $data['name'] = $name;
        $data['description'] = trim((string)($data['description'] ?? ''));
        $data['location'] = trim((string)($data['location'] ?? ''));
        $data['currency'] = strtoupper(trim((string)($data['currency'] ?? EventDefaults::CURRENCY))) ?: EventDefaults::CURRENCY;
        $data['primary_color'] = $this->normalizeHexColor((string)($data['primary_color'] ?? ''), EventDefaults::PRIMARY_COLOR);
        $data['accent_color'] = $this->normalizeHexColor((string)($data['accent_color'] ?? ''), EventDefaults::ACCENT_COLOR);

        $eventStub = (object)[
            'name' => $name,
        ];

        if (trim((string)($data['email_subject'] ?? '')) === '') {
            $data['email_subject'] = EventDefaults::emailSubject($eventStub);
        }
        if (trim((string)($data['email_message'] ?? '')) === '') {
            $data['email_message'] = EventDefaults::emailMessage($eventStub);
        }
        if (trim((string)($data['email_footer'] ?? '')) === '') {
            $data['email_footer'] = EventDefaults::emailFooter($eventStub);
        }

        return $data;
    }

    private function normalizeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);

        return preg_match('/^#[a-f0-9]{6}$/i', $value) ? $value : $fallback;
    }

    private function defaultTicketCatalog(string $currency = 'MXN', int $capacity = 0): array
    {
        return [[
            'name' => __('Entrada general'),
            'description' => __('Acceso general al evento.'),
            'price' => '0.00',
            'currency' => $currency,
            'capacity' => max(0, $capacity),
            'sort_order' => 0,
            'active' => true,
        ]];
    }

    private function buildTicketTypeCatalog($event): array
    {
        $catalog = [];
        foreach ($event->ticket_types ?? [] as $type) {
            if (!$type->active) {
                continue;
            }
            $price = (float)$type->price;
            $currency = $type->currency ?: ($event->currency ?: 'MXN');
            $catalog[$type->id] = [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'capacity' => $type->capacity,
                'price' => number_format($price, 2, '.', ''),
                'currency' => $currency,
                'label' => sprintf('%s (%s %s)', $type->name, $currency, number_format($price, 2)),
            ];
        }

        return $catalog;
    }

    private function resolveTicketTypeKey(string $value, array $typeCatalog, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        if (isset($typeCatalog[$value])) {
            return $value;
        }

        $normalized = mb_strtolower($value);
        foreach ($typeCatalog as $id => $type) {
            $labels = [
                mb_strtolower($type['label']),
                mb_strtolower($type['name']),
            ];
            if (in_array($normalized, $labels, true)) {
                return $id;
            }
        }

        throw new \RuntimeException(__('El tipo de boleto "{0}" no existe o no está activo.', $value));
    }

    private function assertTicketCatalogAvailability($connection, string $eventId, array $ticketData): void
    {
        foreach ($this->ticketTypeQuantities($ticketData) as $typeId => $quantity) {
            $type = $connection->execute(
                'SELECT id, name, capacity, active FROM ticket_types WHERE id = ? AND event_id = ? FOR UPDATE',
                [$typeId, $eventId]
            )->fetch('assoc');
            if (!$type || !(bool)$type['active']) {
                throw new \RuntimeException(__('Uno de los tipos de boleto no está disponible.'));
            }
            if ($type['capacity'] !== null) {
                $sold = (int)$connection->execute(
                    'SELECT COUNT(*) AS total FROM tickets WHERE event_id = ? AND ticket_type_id = ? AND active = 1',
                    [$eventId, $typeId]
                )->fetch('assoc')['total'];
                $remaining = max(0, (int)$type['capacity'] - $sold);
                if ($quantity > $remaining) {
                    throw new \RuntimeException(__('No hay boletos suficientes para {0}. Disponibles: {1}.', $type['name'], $remaining));
                }
            }
        }
    }

    private function assertStaffTicketTypeAvailability($connection, string $eventId, string $staffId, array $ticketData): void
    {
        foreach ($this->ticketTypeQuantities($ticketData) as $typeId => $quantity) {
            $limit = $connection->execute(
                'SELECT id, sales_limit, sales_count
                 FROM staff_ticket_type_limits
                 WHERE staff_id = ? AND event_id = ? AND ticket_type_id = ? AND active = 1
                 FOR UPDATE',
                [$staffId, $eventId, $typeId]
            )->fetch('assoc');

            if (!$limit || $limit['sales_limit'] === null) {
                continue;
            }

            $remaining = max(0, (int)$limit['sales_limit'] - (int)$limit['sales_count']);
            if ($quantity > $remaining) {
                throw new \RuntimeException(__('El límite asignado para este tipo de boleto permite emitir {0} pases más.', $remaining));
            }
        }
    }

    private function ticketTypeQuantities(array $ticketData): array
    {
        $quantities = [];
        foreach ($ticketData as $ticket) {
            $typeId = (string)($ticket['ticket_type_id'] ?? '');
            if ($typeId === '') {
                continue;
            }
            $quantities[$typeId] = ($quantities[$typeId] ?? 0) + 1;
        }

        return $quantities;
    }

    private function buildReportCover(Worksheet $sheet, $event, string $title, array $metrics, array $formats = []): void
    {
        $moneyLabels = $formats['money'] ?? [];
        $percentLabels = $formats['percent'] ?? [];
        $eventDate = $event->event_date ? $event->event_date->i18nFormat('dd/MM/yyyy HH:mm') : __('Sin fecha');
        $generated = DateTime::now()->i18nFormat('dd/MM/yyyy HH:mm');

        $sheet->fromArray([
            ['EventIC'],
            [$title],
            [],
            [__('Evento'), $event->name],
            [__('Fecha del evento'), $eventDate],
            [__('Ubicación'), $event->location ?: __('Ubicación por confirmar')],
            [__('Generado'), $generated],
            [],
        ], null, 'A1', true);

        $row = 9;
        foreach ($metrics as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            if (in_array($label, $moneyLabels, true)) {
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if (in_array($label, $percentLabels, true)) {
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            }
            $row++;
        }

        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF76132C');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB('FF17202A');
        $sheet->getStyle('A4:A7')->getFont()->setBold(true)->getColor()->setARGB('FF687385');
        $sheet->getStyle("A9:A" . max(9, $row - 1))->getFont()->setBold(true)->getColor()->setARGB('FF687385');
        $sheet->getStyle("B9:B" . max(9, $row - 1))->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A9:B" . max(9, $row - 1))->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E9EF']],
            ],
        ]);
        $sheet->getStyle('A1:D' . max(12, $row))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(34);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->setShowGridlines(false);
    }

    private function styleDataSheet(Worksheet $sheet, int $headerRow, array $moneyColumns = [], array $percentColumns = []): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $headerRange = "A{$headerRow}:{$highestColumn}{$headerRow}";

        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$highestColumn}{$highestRow}");
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF76132C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E9EF']],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            if (($row - $headerRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFF8FAFC');
            }
        }

        foreach ($moneyColumns as $column) {
            $sheet->getStyle("{$column}" . ($headerRow + 1) . ":{$column}{$highestRow}")
                ->getNumberFormat()
                ->setFormatCode('"$"#,##0.00');
        }
        foreach ($percentColumns as $column) {
            $sheet->getStyle("{$column}" . ($headerRow + 1) . ":{$column}{$highestRow}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        }
        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
    }

    private function formatReportDate($value): string
    {
        return $value ? $value->i18nFormat('dd/MM/yyyy HH:mm') : '';
    }

    private function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => __('Pagado'),
            'pending' => __('Pendiente'),
            'failed' => __('Fallido'),
            'refunded' => __('Reembolsado'),
            'cancelled' => __('Cancelado'),
            default => __('Gratis'),
        };
    }

    private function downloadSpreadsheet($event, string $reportName, Spreadsheet $spreadsheet)
    {
        $spreadsheet->setActiveSheetIndex(0);
        $filename = Text::slug($event->name . '-' . $reportName) . '.xlsx';
        $path = tempnam(TMP, 'eventic-report-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $this->response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withDownload($filename)
            ->withFile($path, ['download' => true, 'name' => $filename]);
    }
}
