<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    public function update(User $user, object $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($fulfillment));
    }

    public function cancel(User $user, object $fulfillment): bool
    {
        return $this->update($user, $fulfillment);
    }
}
