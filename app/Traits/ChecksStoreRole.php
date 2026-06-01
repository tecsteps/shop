<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;

/**
 * Shared role-checking helpers for policies and gates.
 *
 * The store id is taken from a model's `store_id` when a model is available,
 * otherwise from the container-bound `current_store` (set by ResolveStore).
 */
trait ChecksStoreRole
{
    /**
     * The user's role for a given store, or null if they have none.
     */
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $membership = $user->stores()
            ->withoutGlobalScopes()
            ->wherePivot('store_id', $storeId)
            ->first();

        if ($membership === null) {
            return null;
        }

        $role = $membership->membership->role;

        return $role instanceof StoreUserRole ? $role : StoreUserRole::from((string) $role);
    }

    /**
     * Whether the user's role for the store is in the provided list.
     *
     * @param  list<StoreUserRole>  $roles
     */
    protected function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    /**
     * Shorthand: the user is an Owner or Admin of the store.
     */
    protected function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, StoreUserRole::ownerOrAdmin());
    }

    /**
     * Shorthand: the user is an Owner, Admin, or Staff of the store.
     */
    protected function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, StoreUserRole::ownerAdminOrStaff());
    }

    /**
     * Shorthand: the user is an Owner of the store.
     */
    protected function isOwner(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, StoreUserRole::ownerOnly());
    }

    /**
     * Whether the user has any role at all in the store.
     */
    protected function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    /**
     * Resolve the current store id from the container, or null when unbound.
     */
    protected function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        return app('current_store')->id;
    }
}
