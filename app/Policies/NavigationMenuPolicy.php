<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class NavigationMenuPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return app()->bound('current_store') && $this->isOwnerAdminOrStaff($user, app('current_store')->id);
    }

    public function manage(User $user): bool
    {
        return app()->bound('current_store') && $this->isOwnerOrAdmin($user, app('current_store')->id);
    }
}
