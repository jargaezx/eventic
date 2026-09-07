<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Routing\Router;

/**
 * RBAC helper
 */
class RBACHelper extends Helper
{
    protected array $_defaultConfig = [];

    public array $helpers = ['Html', 'Form'];

    public function link($title, $url = null, $options = [])
    {
        $request = $this->getView()->getRequest();
        if( $this->can($url) ){
            if(Router::reverse($request->getAttribute('params')) == Router::reverse($url)){
                @$options['class'].= ' active';
            }
            return $this->Html->link($title, $url, $options);
        }
        $options['data-bs-toggle'] = 'tooltip';
        $options['data-bs-title'] = __('No cuenta con los permisos necesarios, consulte a su administrador del sistema.');
        @$options['class'] .= ' text-muted opacity-25 disabled';
        return $this->Html->link($title, '#', $options);
    }

    public function postLink($title, $url = null, $options = [])
    {
        $request = $this->getView()->getRequest();
        if( $this->can($url) ){
            if(Router::reverse($request->getAttribute('params')) == Router::reverse($url)){
                @$options['class'].= ' active';
            }
            return $this->Html->link($title, $url, $options);
        }
        $options['data-bs-toggle'] = 'tooltip';
        $options['data-bs-title'] = __('No cuenta con los permisos necesarios, consulte a su administrador del sistema.');
        @$options['class'] .= ' text-muted opacity-25 disabled';
        return $this->Form->postLink($title, '#', $options);
    }

    protected function can($url)
    {
        $url = array_intersect_key($url, ['prefix'=>'', 'controller'=>'', 'action'=>'']);
        $request = $this->getView()->getRequest();
        $identity = $request->getAttribute('identity');
        $user = $identity ? $identity->getOriginalData() : $request->getSession()->read('Auth');

        if (!$user) {
            return false;
        }

        if ($user->is_superadmin) {
            return true;
        }

        foreach(($user->role->permissions ?? []) as $permission)
        {
            if(Router::reverse($permission->toArray()) == Router::reverse($url)) return true;
        }
        return false;
    }
}
