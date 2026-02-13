<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any fulfillments.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can view the fulfillment.
     */
    public function view(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can create fulfillments.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can update the fulfillment.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }
}
