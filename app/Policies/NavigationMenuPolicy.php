<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class NavigationMenuPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function manage(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }
}
