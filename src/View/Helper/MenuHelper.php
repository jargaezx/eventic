<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Routing\Router;
use Cake\View\Helper;
use Cake\View\View;

class MenuHelper extends Helper
{

    protected array $_defaultConfig = [];

    public array $helpers = ['RBAC', 'Html'];

    public function link($title, $url = null, $options = [])
    {
        $request = $this->getView()->getRequest();
        if( $this->RBAC->can($url) ){
            if(Router::reverse($request->getAttribute('params')) == Router::reverse($url)){
                $options['class'].= ' active';
            }
            return $this->Html->link($title, $url, $options);
        }

        $options['class'].= ' link-secondary ';
        $options['data-bs-toggle'] = 'tooltip';
        $options['data-bs-title'] = __('No cuenta con los permisos necesarios, consulte a su administrador del sistema.');
        return $this->Html->tag('span', $title, $options);
    }

    protected function __hasPermission($url)
    {
        $request = $this->getView()->getRequest();
        $session = $request->getSession();
        $user = $session->read('Auth');
        if($user->is_superadmin) return true;
        foreach($user->role->permissions as $permission)
        {
            if(Router::reverse($permission->toArray()) == Router::reverse($url))return true;
        }
        return false;
    }
}
