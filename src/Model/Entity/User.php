<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class User extends Entity
{

    protected array $_accessible = [
        '*' => true
    ];

    protected array $_hidden = [
        'password',
    ];

    protected array $_virtual = ['full_name'];

    protected function _setPassword(string $password) : ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
    }

    protected function _getFullName()
    {
        return implode(' ', [$this->names, $this->last_name]);
    }
}
