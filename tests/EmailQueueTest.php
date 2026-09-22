<?php
declare(strict_types=1);

use App\Command\ProcessEmailQueueCommand;
use App\Model\Entity\User;
use App\Model\Table\EmailJobsTable;
use App\Model\Table\TicketsTable;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

class EmailQueueTest extends TestCase
{
    use IntegrationTestTrait;
    use EmailTrait;

    private EmailJobsTable $jobs;
    private string $eventId;
    private string $otherEventId;
    private string $ownerId;
    private array $temporaryTables = [];

    protected function setUp(): void
    {
        parent::setUp();
        $connection = ConnectionManager::get('test');
        foreach ($connection->getSchemaCollection()->listTables() as $table) {
            $quoted = $connection->getDriver()->quoteIdentifier($table);
            $definition = $connection->execute("SHOW CREATE TABLE {$quoted}")->fetch('num')[1];
            $definition = preg_replace('/^\s*CONSTRAINT [^\n]+\n/m', '', $definition);
            $definition = str_replace(",\n)", "\n)", $definition);
            $connection->execute(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $definition));
            $this->temporaryTables[] = $quoted;
        }
        DateTime::setTestNow(new DateTime($connection->execute('SELECT NOW()')->fetch('num')[0]));
        $this->ownerId = Text::uuid();
        $this->eventId = $this->event();
        $this->otherEventId = $this->event();
        $this->jobs = FactoryLocator::get('Table')->get('EmailJobs');
        // MySQL temporary tables cannot be joined more than once in one query.
        $events = FactoryLocator::get('Table')->get('Events');
        foreach (['Owners', 'CreatedByUsers', 'ModifiedByUsers'] as $association) {
            $events->getAssociation($association)->setStrategy('select');
        }
    }

    protected function tearDown(): void
    {
        DateTime::setTestNow(null);
        FactoryLocator::get('Table')->clear();
        foreach ($this->temporaryTables as $table) {
            ConnectionManager::get('test')->execute("DROP TEMPORARY TABLE {$table}");
        }
        $this->temporaryTables = [];
        parent::tearDown();
    }

    private function insert(string $table, array $data): void
    {
        ConnectionManager::get('test')->insertQuery()->insert(array_keys($data))
            ->into($table)->values($data)->execute();
    }

    private function event(): string
    {
        $id = Text::uuid();
        $this->insert('events', ['id' => $id, 'owner_id' => $this->ownerId, 'name' => 'Queue test', 'active' => 1]);

        return $id;
    }

    private function ticket(bool $active = true): string
    {
        $id = Text::uuid();
        $this->insert('tickets', [
            'id' => $id, 'event_id' => $this->eventId, 'active' => (int)$active,
            'email' => 'queue@example.test', 'name' => 'Queue test',
        ]);

        return $id;
    }

    private function job(array $data = [])
    {
        $job = $this->jobs->newEntity($data + [
            'type' => 'ticket', 'ticket_id' => $this->ticket(), 'event_id' => $this->eventId,
            'recipient_email' => 'queue@example.test', 'status' => 'failed', 'attempts' => 3,
            'max_attempts' => 3, 'created' => DateTime::now()->subHours(1),
        ]);

        return $this->jobs->saveOrFail($job);
    }

    private function login(bool $superadmin = true, ?string $id = null): void
    {
        $this->session(['Auth' => new User([
            'id' => $id ?? $this->ownerId, 'is_superadmin' => $superadmin,
            'names' => 'Queue', 'last_names' => 'Test', 'role' => null,
        ])]);
    }

    public function testCountsAreEventScopedAndScheduledBackoffIsNotOverdue(): void
    {
        $this->job(['status' => 'pending', 'available_at' => DateTime::now()->subMinutes(15)]);
        $this->job(['status' => 'pending', 'available_at' => DateTime::now()->addMinutes(1)]);
        $this->job(['status' => 'processing', 'locked_at' => DateTime::now()->subMinutes(16)]);
        $this->job(['status' => 'processing', 'locked_at' => DateTime::now()]);
        $this->job(['status' => 'sent']);
        $this->job(['status' => 'cancelled']);
        $this->job();
        $this->job(['event_id' => $this->otherEventId]);
        $this->assertSame([
            'total' => 7, 'pending' => 4, 'processing' => 2, 'processed' => 1,
            'failed' => 1, 'cancelled' => 1, 'uncertain' => 0, 'overdue' => 2,
        ], $this->jobs->operationalStatus($this->eventId));
        $this->assertSame(0, $this->jobs->operationalStatus(Text::uuid())['total']);
    }

    public function testRetryReusesOnlyFailedJobsAndRepeatedRequestDoesNothing(): void
    {
        $failed = $this->job(['last_error' => 'SMTP failure', 'locked_by' => 'old-worker']);
        $others = [];
        foreach (['pending', 'processing', 'sent', 'cancelled'] as $status) {
            $others[] = $this->job(['status' => $status]);
        }
        $foreign = $this->job(['event_id' => $this->otherEventId]);
        $count = $this->jobs->find()->count();
        $this->assertSame(['retried' => 1, 'skipped' => 0], $this->jobs->retryFailedForEvent($this->eventId));
        $retried = $this->jobs->get($failed->id);
        $this->assertSame('pending', $retried->status);
        $this->assertSame(0, $retried->attempts);
        $this->assertSame(3, $retried->max_attempts);
        $this->assertNull($retried->last_error);
        $this->assertNull($retried->locked_by);
        $this->assertNull($retried->locked_at);
        $this->assertEquals(DateTime::now(), $retried->available_at);
        $this->assertSame($failed->recipient_email, $retried->recipient_email);
        $this->assertSame(['retried' => 0, 'skipped' => 0], $this->jobs->retryFailedForEvent($this->eventId));
        $this->assertSame($count, $this->jobs->find()->count());
        $this->assertSame('failed', $this->jobs->get($foreign->id)->status);
        foreach ($others as $other) {
            $this->assertSame($other->status, $this->jobs->get($other->id)->status);
        }
    }

    public function testRetrySkipsUnavailableTicketsAndDuplicateDeliveries(): void
    {
        $this->job(['ticket_id' => $this->ticket(false)]);
        $this->job(['ticket_id' => null]);
        $this->job(['type' => 'unsupported']);
        $this->job(['max_attempts' => 0]);
        $this->job(['sent_at' => DateTime::now()]);
        foreach (['pending', 'processing', 'sent'] as $status) {
            $failed = $this->job();
            $this->job(['ticket_id' => $failed->ticket_id, 'status' => $status, 'sent_at' => DateTime::now()]);
        }
        $older = $this->job();
        $newer = $this->job(['ticket_id' => $older->ticket_id, 'created' => DateTime::now()]);
        $this->assertSame(['retried' => 1, 'skipped' => 9], $this->jobs->retryFailedForEvent($this->eventId));
        $this->assertSame('failed', $this->jobs->get($older->id)->status);
        $this->assertSame('pending', $this->jobs->get($newer->id)->status);
    }

    public function testExistingWorkerProcessesRetriedJobAndCancelsInactiveTicket(): void
    {
        $job = $this->job();
        $this->jobs->retryFailedForEvent($this->eventId);
        $cancelled = $this->job(['status' => 'pending', 'attempts' => 0, 'ticket_id' => $this->ticket(false)]);
        $tickets = $this->getMockBuilder(TicketsTable::class)
            ->setConstructorArgs([['alias' => 'Tickets', 'connection' => ConnectionManager::get('test')]])
            ->onlyMethods(['deliverTicketEmail'])->getMock();
        $tickets->expects($this->once())->method('deliverTicketEmail')
            ->with($this->callback(fn ($ticket) => $ticket->id === $job->ticket_id), $job->recipient_email);
        FactoryLocator::get('Table')->set('Tickets', $tickets);
        $command = new ProcessEmailQueueCommand();
        $command->execute(new Arguments([], ['limit' => '50', 'worker' => 'queue-test'], []), $this->createMock(ConsoleIo::class));
        $this->assertSame('sent', $this->jobs->get($job->id)->status);
        $this->assertNotNull($this->jobs->get($job->id)->sent_at);
        $this->assertSame('cancelled', $this->jobs->get($cancelled->id)->status);
        $this->assertSame(1, $this->jobs->operationalStatus($this->eventId)['processed']);
    }

    public function testExistingWorkerFailureBackoffExhaustionAndManualRetry(): void
    {
        $job = $this->job(['status' => 'pending', 'attempts' => 0]);
        $tickets = $this->getMockBuilder(TicketsTable::class)
            ->setConstructorArgs([['alias' => 'Tickets', 'connection' => ConnectionManager::get('test')]])
            ->onlyMethods(['deliverTicketEmail'])->getMock();
        $tickets->expects($this->exactly(3))->method('deliverTicketEmail')->willThrowException(new RuntimeException('Test SMTP failure'));
        FactoryLocator::get('Table')->set('Tickets', $tickets);
        $command = new ProcessEmailQueueCommand();
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $command->execute(new Arguments([], ['limit' => '50', 'worker' => 'queue-test'], []), $this->createMock(ConsoleIo::class));
            $current = $this->jobs->get($job->id);
            $this->assertSame($attempt, $current->attempts);
            $this->assertSame($attempt === 3 ? 'failed' : 'pending', $current->status);
            if ($attempt < 3) {
                $this->assertTrue($current->available_at > DateTime::now());
                $this->assertSame(0, $this->jobs->operationalStatus($this->eventId)['overdue']);
                $this->jobs->updateAll(['available_at' => DateTime::now()->subDays(1)], ['id' => $job->id]);
            }
        }
        $this->assertSame(1, $this->jobs->retryFailedForEvent($this->eventId)['retried']);
        $this->assertSame(0, $this->jobs->get($job->id)->attempts);
    }

    public function testRetryEndpointRequiresPostAndCsrf(): void
    {
        $failed = $this->job();
        $this->login();
        $this->get('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertResponseCode(405);
        $this->post('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertResponseCode(403);
        $this->assertSame('failed', $this->jobs->get($failed->id)->status);
    }

    public function testOwnerCanRetryAndOtherEventIsUntouched(): void
    {
        $failed = $this->job();
        $foreign = $this->job(['event_id' => $this->otherEventId]);
        $this->login(false);
        $this->enableCsrfToken();
        $this->post('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertRedirect('/admin/events/view/' . $this->eventId . '#email-queue');
        $this->assertSame('pending', $this->jobs->get($failed->id)->status);
        $this->assertSame('failed', $this->jobs->get($foreign->id)->status);
    }

    public function testUnassignedUserCannotRetryOrViewEvent(): void
    {
        $failed = $this->job();
        $this->login(false, Text::uuid());
        $this->enableCsrfToken();
        $this->post('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertHeader('Location', '/admin/login');
        $this->assertSame('failed', $this->jobs->get($failed->id)->status);
        $this->get('/admin/events/view/' . $this->eventId);
        $this->assertHeader('Location', '/admin/login');
    }

    public function testEventViewRendersCountsAndProtectedRetryForm(): void
    {
        $this->job();
        $this->login();
        $this->get('/admin/events/view/' . $this->eventId);
        $this->assertResponseOk();
        $this->assertResponseContains('Cola de correos');
        foreach (['pending', 'processed', 'failed', 'overdue'] as $status) {
            $this->assertResponseContains('data-email-queue-count="' . $status . '"');
        }
        $this->assertResponseContains('Reintentar fallidos');
        $this->assertResponseContains('name="_csrfToken"');
    }

    public function testEnqueueReusesRetriedJobAndRealMailerBuildsTicket(): void
    {
        FactoryLocator::get('Table')->get('Events')->updateAll([
            'event_date' => new DateTime('2026-10-24 18:30:00', 'America/Mexico_City'),
        ], ['id' => $this->eventId]);
        $job = $this->job();
        $this->jobs->retryFailedForEvent($this->eventId);
        $tickets = FactoryLocator::get('Table')->get('Tickets');
        $ticket = $tickets->get($job->ticket_id);
        $this->assertSame($job->id, $tickets->queueTicketEmail($ticket)->id);
        try {
            (new ProcessEmailQueueCommand())->execute(
                new Arguments([], ['limit' => '50', 'worker' => 'queue-test'], []),
                $this->createMock(ConsoleIo::class)
            );
            $this->assertSame('sent', $this->jobs->get($job->id)->status);
            $this->assertMailCount(1);
            $this->assertMailSentTo('queue@example.test');
            $this->assertMailContains('24/10/2026, 18:30');
            $this->assertMailContains('cid:ticket-qr');
            $this->assertNotNull($tickets->get($ticket->id)->last_emailed);
            $this->assertSame(1, $tickets->get($ticket->id)->email_attempt_count);
        } finally {
            $path = WWW_ROOT . 'files' . DS . 'tickets' . DS . $ticket->id . '.png';
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testScannerCanViewButCannotRetryAndSellerCanRetry(): void
    {
        $failed = $this->job();
        $staffId = Text::uuid();
        $userId = Text::uuid();
        $this->insert('staffs', [
            'id' => $staffId, 'event_id' => $this->eventId, 'user_id' => $userId,
            'active' => 1, 'can_scan' => 1, 'can_register' => 0, 'can_manage_event' => 0,
        ]);
        $this->login(false, $userId);
        $this->get('/admin/events/view/' . $this->eventId);
        $this->assertResponseOk();
        $this->assertResponseContains('Cola de correos');
        $this->assertResponseNotContains('id="retry-failed-emails"');
        $this->enableCsrfToken();
        $this->post('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertHeader('Location', '/admin/login');
        $this->assertSame('failed', $this->jobs->get($failed->id)->status);

        FactoryLocator::get('Table')->get('Staffs')->updateAll(['can_register' => true], ['id' => $staffId]);
        $this->post('/admin/events/retry-failed-emails/' . $this->eventId);
        $this->assertRedirect('/admin/events/view/' . $this->eventId . '#email-queue');
        $this->assertSame('pending', $this->jobs->get($failed->id)->status);
    }
    public function testInterruptedPreparationRecoversButSmtpRequiresReview(): void
    {
        $preparing = $this->job(['status' => 'preparing', 'attempts' => 0, 'locked_at' => DateTime::now()->subMinutes(16)]);
        $sending = $this->job(['status' => 'processing', 'attempts' => 0, 'locked_at' => DateTime::now()->subMinutes(16)]);
        $fresh = $this->job(['status' => 'preparing', 'attempts' => 0, 'locked_at' => DateTime::now()]);
        $this->assertSame(['requeued' => 1, 'uncertain' => 1], $this->jobs->recoverInterrupted());
        $this->assertSame('pending', $this->jobs->get($preparing->id)->status);
        $this->assertSame('uncertain', $this->jobs->get($sending->id)->status);
        $this->assertSame('preparing', $this->jobs->get($fresh->id)->status);
        $this->assertSame(0, $this->jobs->retryFailedForEvent($this->eventId)['retried']);
        $this->jobs->resolveUncertain($this->eventId, $sending->id, 'sent');
        $this->assertSame('sent', $this->jobs->get($sending->id)->status);
    }

    public function testLiveWorkerLockPreventsRecoveryAndStaleWorkerCannotFinish(): void
    {
        $job = $this->job(['status' => 'processing', 'locked_by' => 'old', 'locked_at' => DateTime::now()->subHours(1)]);
        $config = ConnectionManager::get('test')->config();
        $otherConnection = new \Cake\Database\Connection($config);
        $otherJobs = new EmailJobsTable(['connection' => $otherConnection]);
        $this->assertTrue($otherJobs->acquireJobLock($job->id));
        try {
            $this->assertSame(['requeued' => 0, 'uncertain' => 0], $this->jobs->recoverInterrupted());
        } finally {
            $otherJobs->releaseJobLock($job->id);
        }
        $this->jobs->recoverInterrupted();
        $this->expectException(RuntimeException::class);
        $this->jobs->markSent($job);
    }

    public function testFailureAfterSmtpStartsIsNotRetriedAutomatically(): void
    {
        $job = $this->job(['status' => 'pending', 'attempts' => 0]);
        $tickets = $this->getMockBuilder(TicketsTable::class)
            ->setConstructorArgs([['alias' => 'Tickets', 'connection' => ConnectionManager::get('test')]])
            ->onlyMethods(['deliverTicketEmail'])->getMock();
        $tickets->method('deliverTicketEmail')->willReturnCallback(function ($ticket, $email, $beforeSend): void {
            $beforeSend();
            throw new RuntimeException('SMTP connection lost after DATA');
        });
        FactoryLocator::get('Table')->set('Tickets', $tickets);
        $code = (new ProcessEmailQueueCommand())->execute(
            new Arguments([], ['limit' => '50', 'worker' => 'queue-test'], []), $this->createMock(ConsoleIo::class)
        );
        $this->assertSame(1, $code);
        $this->assertSame('uncertain', $this->jobs->get($job->id)->status);
        $this->assertSame([], $this->jobs->claimPending(10, 'another-worker'));
        $this->jobs->resolveUncertain($this->eventId, $job->id, 'retry');
        $this->assertSame('pending', $this->jobs->get($job->id)->status);
    }

    public function testUncertainResolutionRequiresVerificationAndEventScope(): void
    {
        $job = $this->job(['status' => 'uncertain']);
        $this->login();
        $this->enableCsrfToken();
        $url = '/admin/events/resolve-email-delivery/' . $this->eventId . '/' . $job->id;
        $this->post($url, ['decision' => 'retry']);
        $this->assertSame('uncertain', $this->jobs->get($job->id)->status);
        $this->post('/admin/events/resolve-email-delivery/' . $this->otherEventId . '/' . $job->id, ['decision' => 'retry', 'verified' => '1']);
        $this->assertSame('uncertain', $this->jobs->get($job->id)->status);
        $this->post($url, ['decision' => 'retry', 'verified' => '1']);
        $this->assertSame('pending', $this->jobs->get($job->id)->status);
        $this->post($url, ['decision' => 'retry', 'verified' => '1']);
        $this->assertSame('pending', $this->jobs->get($job->id)->status);
    }

    public function testScanIsAtomicAndDuplicateDoesNotIncrementCount(): void
    {
        $ticketId = $this->ticket();
        $this->login();
        $url = '/api/tickets/attend/' . $ticketId . '.json';
        $this->post($url, ['event_id' => $this->eventId]);
        $this->assertResponseOk();
        $this->assertSame('valid', json_decode((string)$this->_response->getBody(), true)['status']);
        $this->post($url, ['event_id' => $this->eventId]);
        $this->assertResponseOk();
        $this->assertSame('duplicate', json_decode((string)$this->_response->getBody(), true)['status']);
        $this->assertSame(1, (int)FactoryLocator::get('Table')->get('Events')->get($this->eventId)->ticket_attended_count);
    }

    public function testApiSessionMethodAndServerErrorsAlwaysReturnJson(): void
    {
        $url = '/api/tickets/attend/' . $this->ticket() . '.json';
        $this->post($url, ['event_id' => $this->eventId]);
        $this->assertResponseCode(401);
        $this->assertSame('unauthenticated', json_decode((string)$this->_response->getBody(), true)['status']);
        $this->login();
        $this->get($url);
        $this->assertResponseCode(405);
        $this->assertStringContainsString('application/json', $this->_response->getHeaderLine('Content-Type'));
        $tickets = $this->getMockBuilder(TicketsTable::class)
            ->setConstructorArgs([['alias' => 'Tickets', 'connection' => ConnectionManager::get('test')]])
            ->onlyMethods(['checkIn'])->getMock();
        $tickets->method('checkIn')->willThrowException(new RuntimeException('Internal database failure details'));
        FactoryLocator::get('Table')->set('Tickets', $tickets);
        $this->post($url, ['event_id' => $this->eventId]);
        $this->assertResponseCode(500);
        $body = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('error', $body['status']);
        $this->assertStringNotContainsString('Internal database', $body['message']);
    }

    public function testCounterFailureRollsBackAttendance(): void
    {
        $ticketId = $this->ticket();
        $connection = $this->getMockBuilder(\Cake\Database\Connection::class)
            ->setConstructorArgs([['driver' => ConnectionManager::get('test')->getDriver()]])
            ->onlyMethods(['execute'])->getMock();
        $connection->method('execute')->willThrowException(new RuntimeException('Counter update failed'));
        $tickets = new TicketsTable(['alias' => 'Tickets', 'connection' => $connection]);
        try {
            $tickets->checkIn($ticketId, $this->eventId, $this->ownerId, '127.0.0.1', 'Test');
            $this->fail('Expected failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Counter update failed', $exception->getMessage());
        }
        $this->assertNull(FactoryLocator::get('Table')->get('Tickets')->get($ticketId)->attended);
        $this->assertSame(0, (int)FactoryLocator::get('Table')->get('Events')->get($this->eventId)->ticket_attended_count);
    }

}
