<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\View\JsonView;

use App\Controller\Api\Traits\JsonResponseTrait;


class AppController extends Controller
{
    use JsonResponseTrait;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');

    }

    public function viewClasses(): array
    {
        return [JsonView::class];
    }
}
