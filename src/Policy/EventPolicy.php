<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Event;
use Authorization\IdentityInterface;
use Cake\Datasource\FactoryLocator;

class EventPolicy
{
    private function staff(IdentityInterface $user, Event $event)
    {
        if (!$event->id || !$user->id) {
            return null;
        }

        return FactoryLocator::get('Table')->get('Staffs')
            ->userAssignment($event->id, $user->id);
    }

    public function canEdit(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staff = $this->staff($user, $event);
        if ($staff && $staff->can_manage_event) return true;
        return false;
    }

    public function canAddStaff(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staff = $this->staff($user, $event);
        if ($staff && $staff->can_manage_staff) return true;
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
        $staff = $this->staff($user, $event);
        if($staff)return true;
        return false;
    }

    public function canRegister(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staff = $this->staff($user, $event);
        if($staff && ($staff->can_register || $staff->register))return true;
        return false;
    }

    public function canScan(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staff = $this->staff($user, $event);
        if($staff && ($staff->can_scan || $staff->scan))return true;
        return false;
    }

    public function canReport(IdentityInterface $user, Event $event)
    {
        if($user->is_superadmin || $event->owner_id == $user->id) return true;
        $staff = $this->staff($user, $event);
        if($staff && $staff->can_view_reports)return true;
        return false;
    }

}
