<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\Policy\RequestPolicyInterface;
use Cake\Http\ServerRequest;
use Authorization\Policy\ResultInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Routing\Router;

class RequestPolicy implements RequestPolicyInterface
{
    public function canAccess($identity, ServerRequest $request): bool|ResultInterface
    {
        if (($request->getParam('prefix') === NULL)) {
            return true;
        }

        if($identity && $identity->is_superadmin){
            return true;
        }

        if (!$identity) {
            return false;
        }

        $permissions = $identity->getOriginalData()->role->permissions ?? [];
        foreach($permissions as $permission)
        {
            $url = array_intersect_key($request->getAttribute('params'), ['prefix'=>'', 'controller'=>'', 'action'=>'']);
            if(  Router::reverse($permission->toArray()) == Router::reverse($url) )
            return true;
        }

        if ($request->getParam('prefix') === 'Admin' && $request->getParam('controller') === 'Events') {
            return $this->canAccessAdminEventRoute($identity, $request);
        }

        return false;
    }

    private function canAccessAdminEventRoute($identity, ServerRequest $request): bool
    {
        $action = (string)$request->getParam('action');
        $pass = (array)$request->getParam('pass');
        $eventId = $pass[0] ?? null;
        $staffs = FactoryLocator::get('Table')->get('Staffs');

        if ($action === 'index') {
            return $staffs->find()
                ->where([
                    'Staffs.user_id' => $identity->id,
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

        $staff = $staffs->userAssignment((string)$eventId, $identity->id);
        if (!$staff) {
            return false;
        }

        return match ($action) {
            'view' => true,
            'edit', 'editQR' => (bool)$staff->can_manage_event,
            'addStaff' => (bool)$staff->can_manage_staff,
            'register', 'checkout' => (bool)($staff->can_register || $staff->register),
            'scan' => (bool)($staff->can_scan || $staff->scan),
            'report', 'exportSales', 'exportAttendance' => (bool)$staff->can_view_reports,
            default => false,
        };
    }
}
