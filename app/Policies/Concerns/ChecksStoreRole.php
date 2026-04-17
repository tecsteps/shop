<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    /**
     * Check if the user has one of the given roles for the current store.
     *
     * @param  array<StoreUserRole>  $allowedRoles
     */
    protected function hasRole(User $user, array $allowedRoles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        /** @var Store $store */
        $store = app('current_store');

        $role = $user->roleForStore($store);

        if (! $role) {
            return false;
        }

        return in_array($role, $allowedRoles);
    }

    /**
     * Check if the user is at least an owner or admin.
     */
    protected function isOwnerOrAdmin(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    /**
     * Check if the user is owner, admin, or staff.
     */
    protected function isOwnerAdminOrStaff(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    /**
     * Check if the user has any store role.
     */
    protected function hasAnyRole(User $user): bool
    {
        return $this->hasRole($user, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
            StoreUserRole::Support,
        ]);
    }
}
