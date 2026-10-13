<?php
declare(strict_types=1);

use App\Service\TicketAllocationService;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class TicketAllocationTest extends TestCase
{
    private array $tables = ['events', 'ticket_types', 'tickets', 'staffs', 'staff_ticket_type_limits'];
    private TicketAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $db = ConnectionManager::get('test');
        foreach ($this->tables as $table) {
            $definition = $db->execute("SHOW CREATE TABLE `{$table}`")->fetch('num')[1];
            $definition = preg_replace('/^\s*CONSTRAINT [^\n]+\n/m', '', $definition);
            $definition = str_replace(",\n)", "\n)", $definition);
            $db->execute(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $definition));
        }
        $this->service = new TicketAllocationService($db);
        $this->insert('events', ['id' => 'event', 'name' => 'Allocation test', 'capacity' => 12, 'active' => 1]);
        $this->insert('ticket_types', ['id' => 'general', 'event_id' => 'event', 'name' => 'General', 'capacity' => 10, 'active' => 1]);
        $this->insert('ticket_types', ['id' => 'vip', 'event_id' => 'event', 'name' => 'VIP', 'capacity' => 2, 'active' => 1]);
        $this->insert('staffs', ['id' => 'seller', 'event_id' => 'event', 'user_id' => 'valeria', 'can_register' => 1, 'sales_limit' => 10, 'active' => 1]);
        foreach (['general' => 9, 'vip' => 1] as $type => $limit) {
            $this->insert('staff_ticket_type_limits', ['id' => $type, 'event_id' => 'event', 'staff_id' => 'seller', 'ticket_type_id' => $type, 'sales_limit' => $limit, 'active' => 1]);
            $this->insert('tickets', ['id' => $type, 'event_id' => 'event', 'ticket_type_id' => $type, 'registered_by' => 'valeria', 'name' => 'Test', 'email' => 'test@example.test', 'active' => 1]);
        }
    }

    private function insert(string $table, array $data): void
    {
        ConnectionManager::get('test')->insertQuery()->insert(array_keys($data))->into($table)->values($data)->execute();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            ConnectionManager::get('test')->execute("DROP TEMPORARY TABLE `{$table}`");
        }
        parent::tearDown();
    }

    public function testSoldTicketsDoNotCountTwiceAgainstReservations(): void
    {
        $a = $this->service->availability('event');
        $this->assertSame(2, $a['sold']);
        $this->assertSame(8, $a['reserved']);
        $this->assertSame(2, $a['free']);
        $this->assertSame(1, $a['types']['general']['free']);
        $this->assertSame(1, $a['types']['vip']['free']);
        $own = $this->service->availability('event', 'valeria');
        $this->assertSame(10, $own['free']);
        $this->assertSame(9, $own['types']['general']['free']);
        $this->service->assertValid('event');
    }

    public function testUnreservedSalesReduceAssignableStock(): void
    {
        $this->insert('tickets', ['id' => 'other', 'event_id' => 'event', 'ticket_type_id' => 'general', 'registered_by' => 'admin', 'name' => 'Other', 'email' => 'other@example.test', 'active' => 1]);
        $a = $this->service->availability('event');
        $this->assertSame(1, $a['free']);
        $this->assertSame(0, $a['types']['general']['free']);
        $this->assertSame(1, $a['types']['vip']['free']);
    }

    public function testOverAllocationIsRejected(): void
    {
        $this->insert('staffs', ['id' => 'other', 'event_id' => 'event', 'user_id' => 'mariana', 'can_register' => 1, 'sales_limit' => 3, 'active' => 1]);
        $this->expectException(RuntimeException::class);
        $this->service->assertValid('event');
    }

    public function testInactiveSellerReleasesOnlyUnsoldReservations(): void
    {
        ConnectionManager::get('test')->update('staffs', ['active' => 0], ['id' => 'seller']);
        $a = $this->service->availability('event');
        $this->assertSame(2, $a['sold']);
        $this->assertSame(0, $a['reserved']);
        $this->assertSame(10, $a['free']);
    }

    public function testAdministratorCannotEmitTicketsReservedToSellers(): void
    {
        $this->insert('staffs', ['id' => 'other', 'event_id' => 'event', 'user_id' => 'mariana', 'can_register' => 1, 'sales_limit' => 2, 'active' => 1]);
        $controller = new class(new \Cake\Http\ServerRequest()) extends \App\Controller\Admin\EventsController {
            public \App\Model\Table\EventsTable $Events;
        };
        $controller->Events = \Cake\Datasource\FactoryLocator::get('Table')->get('Events');
        $method = new ReflectionMethod($controller, 'saveTicketsWithCapacityControl');
        $this->assertSame(0, $this->service->availability('event')['free']);
        try {
            $method->invoke($controller, 'event', 'administrator', [['ticket_type_id' => 'general']], true);
            $this->fail('Administrator used reserved stock');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('reservados', $exception->getMessage());
        }
        $this->assertSame(2, $this->service->availability('event')['sold']);
        \Cake\Datasource\FactoryLocator::get('Table')->clear();
    }

    public function testSaleAvailabilityRespectsOwnQuotaAndOtherReservations(): void
    {
        $seller = $this->service->saleAvailability('event', 'valeria');
        $this->assertSame(8, $seller['available']);
        $this->assertSame(8, $seller['types']['general']['available']);
        $this->assertSame(0, $seller['types']['vip']['available']);
        $admin = $this->service->saleAvailability('event', 'administrator', true);
        $this->assertSame(2, $admin['available']);
        $this->assertSame(1, $admin['types']['general']['available']);
        $this->assertSame(1, $admin['types']['vip']['available']);
    }
}
