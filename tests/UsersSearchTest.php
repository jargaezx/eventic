<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;

class UsersSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $connection = ConnectionManager::get('test');
        foreach (['users', 'roles'] as $table) {
            $definition = $connection->execute("SHOW CREATE TABLE `{$table}`")->fetch('num')[1];
            $definition = preg_replace('/^\s*CONSTRAINT [^\n]+\n/m', '', $definition);
            $definition = str_replace(",\n)", "\n)", $definition);
            $connection->execute(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $definition));
        }
        $connection->insert('users', [
            'id' => 'a35d9960-faa7-4c74-a4a8-90536e25e100',
            'email' => 'accesos@example.test',
            'names' => 'Mariana',
            'last_names' => 'Hernández López',
            'password' => 'unused',
            'active' => 1,
            'is_superadmin' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        FactoryLocator::get('Table')->clear();
        foreach (['users', 'roles'] as $table) {
            ConnectionManager::get('test')->execute("DROP TEMPORARY TABLE `{$table}`");
        }
        parent::tearDown();
    }

    public function testSearchWithRolesMatchesEmailNameAndSurname(): void
    {
        $users = FactoryLocator::get('Table')->get('Users');
        foreach (['acc', 'Mariana', 'López'] as $term) {
            $query = $users->find('search', search: ['q' => $term], contain: ['Roles']);
            $this->assertSame(1, $query->count(), "Search failed for {$term}");
            $this->assertSame('accesos@example.test', $query->first()->email);
        }
        $this->assertSame(0, $users->find('search', search: ['q' => 'sin-coincidencia'], contain: ['Roles'])->count());
    }
}
