<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class SyncStaffSalesCounters extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'UPDATE staffs s
             LEFT JOIN (
                SELECT event_id, registered_by, COUNT(*) AS total
                FROM tickets
                WHERE active = 1 AND registered_by IS NOT NULL
                GROUP BY event_id, registered_by
             ) totals ON totals.event_id = s.event_id AND totals.registered_by = s.user_id
             SET s.sales_count = COALESCE(totals.total, 0)'
        );
    }

    public function down(): void
    {
    }
}
