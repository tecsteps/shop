<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class NavigationMenuPolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->getStoreId());
    }

    public function manage(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->getStoreId());
    }
}
