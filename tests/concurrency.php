<?php
declare(strict_types=1);

// Standalone concurrency proof. It creates and removes only its own random test schema.
require dirname(__DIR__) . '/vendor/autoload.php';
(new App\Application(dirname(__DIR__) . '/config'))->bootstrap();

use App\Model\Table\TicketsTable;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;

$config = ConnectionManager::getConfig('default');
unset($config['name'], $config['url']);
$config['persistent'] = false;
$config['cacheMetadata'] = false;

if (($argv[1] ?? '') === 'worker') {
    $schema = $argv[2] ?? '';
    if (!preg_match('/^eventic_test_scan_[a-f0-9]{16}$/', $schema)) {
        exit(2);
    }
    $connection = new Connection(['database' => $schema] + $config);
    $tickets = new TicketsTable(['connection' => $connection]);
    while (microtime(true) < (float)$argv[6]) {
        usleep(10000);
    }
    try {
        $valid = $tickets->checkIn($argv[3], $argv[4], $argv[5], '127.0.0.1', 'Concurrent verification');
        echo json_encode(['status' => $valid ? 'valid' : 'duplicate']);
    } catch (Throwable $exception) {
        echo json_encode(['status' => 'error', 'type' => $exception::class]);
        exit(1);
    }
    exit(0);
}

$source = ConnectionManager::get('default');
$sourceSchema = $source->execute('SELECT DATABASE()')->fetch('num')[0];
$schema = 'eventic_test_scan_' . bin2hex(random_bytes(8));
$quote = fn ($identifier) => $source->getDriver()->quoteIdentifier($identifier);
$source->execute('CREATE DATABASE ' . $quote($schema));
try {
    foreach (['events', 'tickets'] as $table) {
        $source->execute('CREATE TABLE ' . $quote($schema) . '.' . $quote($table)
            . ' LIKE ' . $quote($sourceSchema) . '.' . $quote($table));
    }
    $connection = new Connection(['database' => $schema] + $config);
    $eventId = Text::uuid();
    $userId = Text::uuid();
    $connection->insertQuery()->into('events')->insert(['id', 'name', 'ticket_attended_count'])
        ->values(['id' => $eventId, 'name' => 'Concurrent verification', 'ticket_attended_count' => 0])->execute();
    $ids = [];
    for ($i = 0; $i < 26; $i++) {
        $ids[] = $id = Text::uuid();
        $connection->insertQuery()->into('tickets')->insert(['id', 'event_id', 'name', 'email', 'active', 'folio'])
            ->values(['id' => $id, 'event_id' => $eventId, 'name' => 'Concurrent test', 'email' => 'test@example.test', 'active' => 1, 'folio' => $i + 1])->execute();
    }
    foreach (['same_ticket' => array_fill(0, 25, $ids[0]), 'different_tickets' => array_slice($ids, 1)] as $scenario => $ticketIds) {
        $workers = [];
        $start = (string)(microtime(true) + 5);
        foreach ($ticketIds as $ticketId) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __FILE__, 'worker', $schema, $ticketId, $eventId, $userId, $start],
                [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__));
            if (!is_resource($process)) {
                throw new RuntimeException('Could not start worker');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $counts = ['valid' => 0, 'duplicate' => 0];
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $payload = json_decode($output, true);
            if ($exit !== 0 || !isset($counts[$payload['status'] ?? ''])) {
                throw new RuntimeException('Concurrent worker failed: ' . $output . $errors);
            }
            $counts[$payload['status']]++;
        }
        $expected = $scenario === 'same_ticket' ? ['valid' => 1, 'duplicate' => 24] : ['valid' => 25, 'duplicate' => 0];
        if ($counts !== $expected) {
            throw new RuntimeException('Unexpected concurrent result');
        }
        echo json_encode(['scenario' => $scenario, 'requests' => 25] + $counts), PHP_EOL;
    }
    $count = (int)$connection->execute('SELECT ticket_attended_count FROM events WHERE id = ?', [$eventId])->fetch('num')[0];
    $actual = (int)$connection->execute('SELECT COUNT(*) FROM tickets WHERE attended IS NOT NULL')->fetch('num')[0];
    if ($count !== 26 || $actual !== 26) {
        throw new RuntimeException('Attendance counter mismatch');
    }
    echo json_encode(['event_count' => $count, 'checked_in_tickets' => $actual]), PHP_EOL;
} finally {
    // $schema is generated above, with a fixed prefix and random hexadecimal suffix.
    $source->execute('DROP DATABASE ' . $quote($schema));
}
