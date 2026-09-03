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

        $permissions = $identity->getOriginalData()->role->permissions;
        foreach($permissions as $permission)
        {
            $url = array_intersect_key($request->getAttribute('params'), ['prefix'=>'', 'controller'=>'', 'action'=>'']);
            if(  Router::reverse($permission->toArray()) == Router::reverse($url) )
            return true;
        }
        return false;
    }

}
