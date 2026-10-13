<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Database\Connection;

class TicketAllocationService
{
    public function __construct(private Connection $connection) {}

    public function availability(string $eventId, ?string $excludeUser = null): array
    {
        $db = $this->connection;
        $event = $db->execute('SELECT capacity FROM events WHERE id = ?', [$eventId])->fetch('assoc');
        $types = $db->execute('SELECT id, name, capacity FROM ticket_types WHERE event_id = ? AND active = 1', [$eventId])->fetchAll('assoc');
        $sold = $db->execute('SELECT registered_by, ticket_type_id, COUNT(*) AS quantity FROM tickets WHERE event_id = ? AND active = 1 GROUP BY registered_by, ticket_type_id', [$eventId])->fetchAll('assoc');
        $soldTotal = 0;
        $soldTypes = $soldUsers = $soldUserTypes = [];
        foreach ($sold as $row) {
            $q = (int)$row['quantity'];
            $user = (string)$row['registered_by'];
            $type = (string)$row['ticket_type_id'];
            $soldTotal += $q;
            $soldTypes[$type] = ($soldTypes[$type] ?? 0) + $q;
            $soldUsers[$user] = ($soldUsers[$user] ?? 0) + $q;
            $soldUserTypes[$user][$type] = $q;
        }
        $reserved = 0;
        $assigned = 0;
        $points = [];
        $reservedTypes = [];
        $staffs = $db->execute('SELECT id, user_id, sales_limit FROM staffs WHERE event_id = ? AND active = 1 AND can_register = 1', [$eventId])->fetchAll('assoc');
        foreach ($staffs as $staff) {
            $user = (string)$staff['user_id'];
            if ($user === $excludeUser) {
                continue;
            }
            $typeReserved = 0;
            $typeAssigned = 0;
            $limits = $db->execute('SELECT ticket_type_id, sales_limit FROM staff_ticket_type_limits WHERE staff_id = ? AND active = 1 AND sales_limit IS NOT NULL', [$staff['id']])->fetchAll('assoc');
            foreach ($limits as $limit) {
                $type = (string)$limit['ticket_type_id'];
                $remaining = max(0, (int)$limit['sales_limit'] - ($soldUserTypes[$user][$type] ?? 0));
                $reservedTypes[$type] = ($reservedTypes[$type] ?? 0) + $remaining;
                $typeReserved += $remaining;
                $typeAssigned += (int)$limit['sales_limit'];
            }
            $globalReserved = $staff['sales_limit'] === null ? 0 : max(0, (int)$staff['sales_limit'] - ($soldUsers[$user] ?? 0));
            $reserved += max($globalReserved, $typeReserved);
            $assigned += max((int)($staff['sales_limit'] ?? 0), $typeAssigned);
            $points[$user] = [
                'assigned' => max((int)($staff['sales_limit'] ?? 0), $typeAssigned),
                'sold' => $soldUsers[$user] ?? 0,
                'reserved' => max($globalReserved, $typeReserved),
                'has_quota' => $staff['sales_limit'] !== null || $limits !== [],
                'types' => array_map(fn ($limit) => [
                    'id' => (string)$limit['ticket_type_id'],
                    'assigned' => (int)$limit['sales_limit'],
                    'sold' => $soldUserTypes[$user][(string)$limit['ticket_type_id']] ?? 0,
                ], $limits),
            ];
        }
        $result = ['assigned' => $assigned, 'sold' => $soldTotal, 'reserved' => $reserved, 'free' => (int)$event['capacity'] - $soldTotal - $reserved, 'types' => []];
        foreach ($types as $type) {
            $id = (string)$type['id'];
            $capacity = $type['capacity'] === null ? (int)$event['capacity'] : (int)$type['capacity'];
            $result['types'][$id] = ['name' => $type['name'], 'sold' => $soldTypes[$id] ?? 0, 'reserved' => $reservedTypes[$id] ?? 0,
                'free' => min($capacity - ($soldTypes[$id] ?? 0) - ($reservedTypes[$id] ?? 0), $result['free']),
                'own_sold' => $excludeUser === null ? 0 : ($soldUserTypes[$excludeUser][$id] ?? 0)];
        }
        $result['own_sold'] = $excludeUser === null ? 0 : ($soldUsers[$excludeUser] ?? 0);
        $result['points'] = $points;
        $result['other_sold'] = $soldTotal - array_sum(array_column($points, 'sold'));
        return $result;
    }

    public function assertValid(string $eventId): void
    {
        $available = $this->availability($eventId);
        if ($available['free'] < 0) {
            throw new \RuntimeException(__('Las ventas y reservas superan la capacidad del evento en {0} boletos.', -$available['free']));
        }
        foreach ($available['types'] as $type) {
            if ($type['free'] < 0) {
                throw new \RuntimeException(__('Las ventas y reservas de {0} superan su capacidad en {1} boletos.', $type['name'], -$type['free']));
            }
        }
    }

    public function saleAvailability(string $eventId, string $userId, bool $privileged = false): array
    {
        $a = $this->availability($eventId, $userId);
        $available = max(0, $a['free']);
        $staff = $this->connection->execute('SELECT id, sales_limit FROM staffs WHERE event_id = ? AND user_id = ? AND active = 1', [$eventId, $userId])->fetch('assoc');
        if (!$privileged && $staff && $staff['sales_limit'] !== null) {
            $available = min($available, max(0, (int)$staff['sales_limit'] - $a['own_sold']));
        }
        $limits = [];
        if (!$privileged && $staff) {
            foreach ($this->connection->execute('SELECT ticket_type_id, sales_limit FROM staff_ticket_type_limits WHERE staff_id = ? AND active = 1 AND sales_limit IS NOT NULL', [$staff['id']])->fetchAll('assoc') as $limit) {
                $limits[(string)$limit['ticket_type_id']] = (int)$limit['sales_limit'];
            }
        }
        foreach ($a['types'] as $id => &$type) {
            $type['available'] = min($available, max(0, $type['free']));
            if (isset($limits[$id])) {
                $type['available'] = min($type['available'], max(0, $limits[$id] - $type['own_sold']));
            }
        }
        unset($type);
        $a['available'] = min($available, array_sum(array_column($a['types'], 'available')));
        return $a;
    }
}
