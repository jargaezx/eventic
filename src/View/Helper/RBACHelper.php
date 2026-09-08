<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Routing\Router;
use Cake\Datasource\FactoryLocator;

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
            return $this->Form->postLink($title, $url, $options);
        }
        $options['data-bs-toggle'] = 'tooltip';
        $options['data-bs-title'] = __('No cuenta con los permisos necesarios, consulte a su administrador del sistema.');
        @$options['class'] .= ' text-muted opacity-25 disabled';
        return $this->Form->postLink($title, '#', $options);
    }

    protected function can($url)
    {
        $request = $this->getView()->getRequest();
        $url += array_intersect_key($request->getAttribute('params'), ['prefix'=>'', 'controller'=>'']);
        $url = array_intersect_key($url, ['prefix'=>'', 'controller'=>'', 'action'=>'']) + array_filter($url, 'is_int', ARRAY_FILTER_USE_KEY);
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

        if (($url['prefix'] ?? null) === 'Admin' && ($url['controller'] ?? null) === 'Events') {
            return $this->canEventRoute($url, $user);
        }

        return false;
    }

    private function canEventRoute(array $url, $user): bool
    {
        $action = (string)($url['action'] ?? '');
        $eventId = null;
        foreach ($url as $key => $value) {
            if (is_int($key)) {
                $eventId = $value;
                break;
            }
        }

        $staffs = FactoryLocator::get('Table')->get('Staffs');
        if ($action === 'index') {
            return $staffs->find()
                ->where([
                    'Staffs.user_id' => $user->id,
                    'Staffs.active' => true,
                    'OR' => [
                        'Staffs.can_manage_event' => true,
                        'Staffs.can_manage_staff' => true,
                        'Staffs.can_register' => true,
                        'Staffs.can_view_reports' => true,
                    ],
                ])
                ->count() > 0;
        }

        if (!$eventId) {
            return false;
        }

        $staff = $staffs->userAssignment((string)$eventId, $user->id);
        if (!$staff) {
            return false;
        }

        return match ($action) {
            'view', 'ticket' => true,
            'edit', 'editQR' => (bool)$staff->can_manage_event,
            'addStaff' => (bool)$staff->can_manage_staff,
            'register', 'checkout', 'downloadBulkTemplate', 'resendTicket', 'cancelTicket' => (bool)($staff->can_manage_event || $staff->can_register || $staff->register),
            'scan' => (bool)($staff->can_scan || $staff->scan),
            'report', 'exportSales', 'exportAttendance' => (bool)$staff->can_view_reports,
            default => false,
        };
    }
}
