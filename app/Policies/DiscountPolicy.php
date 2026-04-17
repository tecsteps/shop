<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any discounts.
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can view the discount.
     */
    public function view(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can create discounts.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can update the discount.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can delete the discount.
     */
    public function delete(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }
}
