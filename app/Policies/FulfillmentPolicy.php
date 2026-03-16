<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->currentStoreId());
    }

    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->currentStoreId());
    }

    public function cancel(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->currentStoreId());
    }
}
