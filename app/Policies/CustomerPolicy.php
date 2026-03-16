<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->currentStoreId());
    }

    public function view(User $user): bool
    {
        return $this->isAnyRole($user, $this->currentStoreId());
    }

    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->currentStoreId());
    }
}
