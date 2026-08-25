<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class NavigationMenuPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $this->currentStoreId());
    }

    public function manage(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $this->currentStoreId());
    }
}
