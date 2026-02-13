<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can manage store settings.
     */
    public function manageSettings(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can delete the store.
     */
    public function delete(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner]);
    }

    /**
     * Determine whether the user can manage staff.
     */
    public function manageStaff(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can manage shipping and tax settings.
     */
    public function manageShippingTax(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
