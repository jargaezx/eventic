<?php
declare(strict_types=1);

// Uses a new empty schema. Every worker invokes the application's sale/allocation code.
require dirname(__DIR__) . '/vendor/autoload.php';
(new App\Application(dirname(__DIR__) . '/config'))->bootstrap();

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;

$config = ConnectionManager::getConfig('default');
unset($config['name'], $config['url']);
$config['persistent'] = false;
$config['cacheMetadata'] = false;

function controller(): App\Controller\Admin\EventsController
{
    $controller = new class(new Cake\Http\ServerRequest()) extends App\Controller\Admin\EventsController {
        public App\Model\Table\EventsTable $Events;
    };
    $controller->Events = FactoryLocator::get('Table')->get('Events');
    return $controller;
}

if (($argv[1] ?? '') === 'worker') {
    $schema = $argv[2];
    if (!preg_match('/^eventic_test_sale_[a-f0-9]{16}$/', $schema)) exit(2);
    ConnectionManager::drop('default');
    ConnectionManager::setConfig('default', ['database' => $schema] + $config);
    $db = ConnectionManager::get('default');
    $payload = json_decode(base64_decode($argv[3]), true, flags: JSON_THROW_ON_ERROR);
    $controller = controller();
    while (microtime(true) < (float)$argv[4]) usleep(5000);
    try {
        if ($payload['mode'] === 'assign') {
            $db->transactional(function () use ($db, $payload, $controller) {
                $db->execute('SELECT id FROM events WHERE id = ? FOR UPDATE', [$payload['event']])->fetch('assoc');
                $staffs = FactoryLocator::get('Table')->get('Staffs');
                $staff = $staffs->find()->where(['event_id' => $payload['event'], 'user_id' => $payload['user']])->firstOrFail();
                $staff->sales_limit = $payload['quantity'];
                $staffs->saveOrFail($staff);
                $event = $controller->Events->get($payload['event'], contain: ['TicketTypes']);
                (new ReflectionMethod($controller, 'saveStaffTicketTypeLimits'))->invoke($controller,
                    FactoryLocator::get('Table')->get('StaffTicketTypeLimits'), $staff, $event,
                    [$payload['type'] => ['sales_limit' => $payload['quantity']]], $payload['quantity']);
                (new ReflectionMethod($controller, 'assertStaffTypeLimitsWithinCapacity'))->invoke($controller, $payload['event']);
            });
        } else {
            $rows = [];
            for ($i = 0; $i < $payload['quantity']; $i++) {
                $rows[] = ['event_id' => $payload['event'], 'user_id' => $payload['user'], 'registered_by' => $payload['user'],
                    'ticket_type_id' => $payload['type'], 'name' => 'Isolated sale ' . $i, 'email' => 'test@example.test',
                    'price' => '10.00', 'currency' => 'MXN', 'active' => true];
            }
            (new ReflectionMethod($controller, 'saveTicketsWithCapacityControl'))->invoke($controller,
                $payload['event'], $payload['user'], $rows, $payload['privileged'] ?? false, $payload['request'] ?? null);
        }
        echo json_encode(['status' => 'accepted']);
    } catch (RuntimeException $e) {
        echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage()]);
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'reason' => $e::class . ': ' . $e->getMessage()]);
        exit(1);
    }
    exit(0);
}

$source = ConnectionManager::get('default');
$sourceSchema = $source->execute('SELECT DATABASE()')->fetch('num')[0];
$schema = 'eventic_test_sale_' . bin2hex(random_bytes(8));
$quote = fn ($s) => $source->getDriver()->quoteIdentifier($s);
$source->execute('CREATE DATABASE ' . $quote($schema));
$checks = [];
try {
    foreach ($source->getSchemaCollection()->listTables() as $table) {
        $source->execute('CREATE TABLE ' . $quote($schema) . '.' . $quote($table) . ' LIKE ' . $quote($sourceSchema) . '.' . $quote($table));
    }
    $db = new Cake\Database\Connection(['database' => $schema] + $config);
    $insert = function ($table, $data) use ($db) { $db->insertQuery()->into($table)->insert(array_keys($data))->values($data)->execute(); };
    $fixture = function (int $capacity, ?int $quota = null, ?int $typeCapacity = null) use ($insert): array {
        $event = Text::uuid(); $type = Text::uuid(); $users = [];
        $insert('events', ['id' => $event, 'name' => 'Isolated concurrency', 'capacity' => $capacity, 'ticket_count' => 0, 'active' => 1]);
        $insert('ticket_types', ['id' => $type, 'event_id' => $event, 'name' => 'General', 'capacity' => $typeCapacity ?? $capacity, 'price' => 10, 'currency' => 'MXN', 'active' => 1]);
        for ($i = 0; $i < 2; $i++) {
            $user = Text::uuid(); $staff = Text::uuid(); $users[] = $user;
            $insert('users', ['id' => $user, 'email' => $user . '@example.test', 'password' => 'unused', 'active' => 1]);
            $insert('staffs', ['id' => $staff, 'event_id' => $event, 'user_id' => $user, 'role' => 'seller', 'active' => 1, 'can_register' => 1, 'sales_limit' => $quota]);
            if ($quota !== null) $insert('staff_ticket_type_limits', ['id' => Text::uuid(), 'event_id' => $event, 'staff_id' => $staff, 'ticket_type_id' => $type, 'sales_limit' => $quota, 'active' => 1]);
        }
        return compact('event', 'type', 'users');
    };
    $run = function (array $payloads) use ($schema): array {
        $workers = []; $start = (string)(microtime(true) + 2);
        foreach ($payloads as $payload) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __FILE__, 'worker', $schema, base64_encode(json_encode($payload)), $start], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, dirname(__DIR__));
            if (!is_resource($process)) throw new RuntimeException('Worker could not start');
            fclose($pipes[0]); $workers[] = [$process, $pipes];
        }
        $results = [];
        foreach ($workers as [$process,$pipes]) {
            $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            $code = proc_close($process); $row = json_decode($out, true);
            if ($code !== 0 || !$row || $row['status'] === 'error') throw new RuntimeException($out . $err);
            $results[] = $row;
        }
        return $results;
    };
    $prove = function ($name, $f, $payloads, $accepted, $tickets, $jobs) use ($run,$db,&$checks) {
        $results = $run($payloads);
        $actualAccepted = count(array_filter($results, fn ($r) => $r['status'] === 'accepted'));
        $actualTickets = (int)$db->execute('SELECT COUNT(*) FROM tickets WHERE event_id = ?', [$f['event']])->fetch('num')[0];
        $actualJobs = (int)$db->execute('SELECT COUNT(*) FROM email_jobs WHERE event_id = ?', [$f['event']])->fetch('num')[0];
        $counter = (int)$db->execute('SELECT ticket_count FROM events WHERE id = ?', [$f['event']])->fetch('num')[0];
        $actualActive = (int)$db->execute('SELECT COUNT(*) FROM tickets WHERE event_id = ? AND active = 1', [$f['event']])->fetch('num')[0];
        if ($actualAccepted !== $accepted || ($tickets >= 0 && $actualTickets !== $tickets) || ($jobs >= 0 && $actualJobs !== $jobs) || $counter !== $actualActive) throw new RuntimeException($name . ': ' . json_encode(compact('results','actualTickets','actualJobs','counter')));
        $checks[] = ['scenario'=>$name,'requests'=>count($payloads),'accepted'=>$actualAccepted,'rejected'=>count($results)-$actualAccepted,'tickets'=>$actualTickets,'emails'=>$actualJobs,'counter'=>$counter];
        echo json_encode(end($checks)), PHP_EOL;
    };
    $sale = fn ($f,$user,$q=1) => ['mode'=>'sale','event'=>$f['event'],'type'=>$f['type'],'user'=>$user,'quantity'=>$q];
    $f=$fixture(1); $prove('last_event_ticket', $f, array_map(fn($i)=>$sale($f,$f['users'][$i%2]),range(1,12)),1,1,1);
    $f=$fixture(10,null,1); $prove('last_ticket_of_type',$f,[$sale($f,$f['users'][0]),$sale($f,$f['users'][1])],1,1,1);
    $f=$fixture(2,1); $prove('same_seller_quota',$f,[$sale($f,$f['users'][0]),$sale($f,$f['users'][0])],1,1,1);
    $f=$fixture(2,1); $prove('two_reserved_sellers',$f,[$sale($f,$f['users'][0]),$sale($f,$f['users'][1])],2,2,2);
    $f=$fixture(2,1); $prove('admin_cannot_take_reservations',$f,[array_merge($sale($f,Text::uuid()),['privileged'=>true])],0,0,0);
    $f=$fixture(1); $prove('batch_rejected_without_partial_sale',$f,[$sale($f,$f['users'][0],2)],0,0,0);
    $f=$fixture(1); $prove('concurrent_assignment',$f,array_map(fn($u)=>['mode'=>'assign','event'=>$f['event'],'type'=>$f['type'],'user'=>$u,'quantity'=>1],$f['users']),1,0,0);
    $allocation=(new App\Service\TicketAllocationService($db))->availability($f['event']);
    if($allocation['reserved']!==1 || $allocation['free']!==0)throw new RuntimeException('Reservation mismatch');
    $f=$fixture(1); $prove('assignment_competes_with_sale',$f,[['mode'=>'assign','event'=>$f['event'],'type'=>$f['type'],'user'=>$f['users'][0],'quantity'=>1],$sale($f,$f['users'][1])],1,
        // The winner determines whether the slot is sold or reserved.
        -1,-1);
    $allocation=(new App\Service\TicketAllocationService($db))->availability($f['event']);
    if ($allocation['sold'] + $allocation['reserved'] !== 1 || $allocation['free'] !== 0) throw new RuntimeException('Mixed operation oversold');
    $f=$fixture(4); $request=Text::uuid();
    $payload=array_merge($sale($f,$f['users'][0]),['request'=>$request]);
    $prove('repeated_same_request',$f,[$payload,$payload],2,1,1);
    $prove('replay_after_response_lost',$f,[$payload],1,1,1);
    $changed=array_merge($payload,['quantity'=>2]);
    $prove('reused_request_with_changed_data',$f,[$changed],0,1,1);
    $new=array_merge($payload,['request'=>Text::uuid()]);
    $prove('new_request_same_attendee_is_independent',$f,[$new],1,2,2);
    $f=$fixture(2); $db->update('staffs', ['active'=>0], ['event_id'=>$f['event'],'user_id'=>$f['users'][0]]);
    $prove('revoked_seller_cannot_sell',$f,[$sale($f,$f['users'][0])],0,0,0);
} finally {
    $source->execute('DROP DATABASE ' . $quote($schema));
}
