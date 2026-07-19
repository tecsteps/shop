<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\StoreUser;
use App\Models\User;

trait ChecksStoreRole
{
    /**
     * Look up the user's role for the given store via the store_users
     * pivot. Returns null when the user has no role in that store.
     */
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $role = StoreUser::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->getKey())
            ->value('role');

        return $role === null ? null : StoreUserRole::from($role);
    }

    /**
     * Determine whether the user's role for the store is in the given list.
     *
     * @param  array<int, StoreUserRole>  $roles
     */
    protected function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    /**
     * Determine whether the user is an owner or admin of the store.
     */
    protected function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    /**
     * Determine whether the user is an owner, admin, or staff of the store.
     */
    protected function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    /**
     * Determine whether the user holds any role in the store.
     */
    protected function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    /**
     * Resolve the store id from the container-bound current store.
     */
    protected function currentStoreId(): ?int
    {
        return app()->bound('current_store') ? (int) app('current_store')->getKey() : null;
    }
}
