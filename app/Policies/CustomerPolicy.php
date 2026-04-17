<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
