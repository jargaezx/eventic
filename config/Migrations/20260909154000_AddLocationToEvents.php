<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddLocationToEvents extends AbstractMigration
{
    public function up(): void
    {
        $events = $this->table('events');
        if (!$events->hasColumn('location')) {
            $events->addColumn('location', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'after' => 'event_date',
            ])->update();
        }

        $events = $this->table('events');
        if ($events->hasColumn('primary_color')) {
            $events->changeColumn('primary_color', 'string', [
                'default' => '#76132c',
                'limit' => 7,
                'null' => false,
            ])->update();
        }
        if ($events->hasColumn('accent_color')) {
            $events->changeColumn('accent_color', 'string', [
                'default' => '#c99a3f',
                'limit' => 7,
                'null' => false,
            ])->update();
        }
    }

    public function down(): void
    {
        $events = $this->table('events');
        if ($events->hasColumn('location')) {
            $events->removeColumn('location')->update();
        }

        $events = $this->table('events');
        if ($events->hasColumn('primary_color')) {
            $events->changeColumn('primary_color', 'string', [
                'default' => '#2563EB',
                'limit' => 7,
                'null' => false,
            ])->update();
        }
        if ($events->hasColumn('accent_color')) {
            $events->changeColumn('accent_color', 'string', [
                'default' => '#16A34A',
                'limit' => 7,
                'null' => false,
            ])->update();
        }
    }
}
