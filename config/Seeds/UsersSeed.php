<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Cake\I18n\DateTime;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * Users seed.
 */
class UsersSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        $this->execute(
            "INSERT INTO users (id, email, password, created, modified, deleted, is_superadmin, active)
             SELECT :id, :email, :password, :created, :modified, NULL, 1, 1
             WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = :email_check)",
            [
                'id' => 'f59065b7-46eb-4219-b85e-347b6c5c294a',
                'email' => 'admin@admin.com',
                'password' => (new DefaultPasswordHasher())->hash('admin.123#A'),
                'created' => DateTime::now()->format('Y-m-d H:i:s'),
                'modified' => DateTime::now()->format('Y-m-d H:i:s'),
                'email_check' => 'admin@admin.com',
            ]
        );
    }
}
