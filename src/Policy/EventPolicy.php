<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Event;
use Authorization\IdentityInterface;
use Cake\Datasource\FactoryLocator;

class EventPolicy
{

    public function canEdit(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        return false;
    }

    public function canAddStaff(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        return false;
    }

    public function canEditQR(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        return false;
    }

    public function canDelete(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        return false;
    }

    public function canView(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staffTable = FactoryLocator::get('Table')->get('Staffs');
        $staff = $staffTable->find('all',
            conditions:['event_id' => $event->id, 'user_id' => $user->id]
        )->first();
        if($staff)return true;
        return false;
    }

    public function canRegister(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staffTable = FactoryLocator::get('Table')->get('Staffs');
        $staff = $staffTable->find('all',
            conditions:['event_id' => $event->id, 'user_id' => $user->id]
        )->first();
        if($staff && $staff->register)return true;
        return false;
    }

    public function canScan(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staffTable = FactoryLocator::get('Table')->get('Staffs');
        $staff = $staffTable->find('all',
            conditions:['event_id' => $event->id, 'user_id' => $user->id]
        )->first();
        if($staff && $staff->scan)return true;
        return false;
    }


}
