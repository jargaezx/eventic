<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class DropLegacyTicketFolioTrigger extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DROP TRIGGER IF EXISTS tickets_BEFORE_INSERT');
    }

    public function down(): void
    {
    }
}
