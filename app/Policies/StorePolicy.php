<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    public function viewSettings(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function updateSettings(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function delete(User $user): bool
    {
        return $this->hasRole($user, $this->currentStoreId(), [StoreUserRole::Owner]);
    }

    public function manageStaff(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }
}
