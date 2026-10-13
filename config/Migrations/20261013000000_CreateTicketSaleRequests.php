<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateTicketSaleRequests extends AbstractMigration
{
    public function change(): void
    {
        $this->table('ticket_sale_requests', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('event_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('payload_hash', 'char', ['limit' => 64, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['event_id', 'user_id'])
            ->create();
    }
}
