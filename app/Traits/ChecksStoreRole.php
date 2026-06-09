<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\StoreUser;
use App\Models\User;

trait ChecksStoreRole
{
    /**
     * Look up the user's role for the given store via the store_users pivot.
     */
    protected function getStoreRole(User $user, ?int $storeId): ?StoreUserRole
    {
        if ($storeId === null) {
            return null;
        }

        return StoreUser::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->getKey())
            ->first()
            ?->role;
    }

    /**
     * Determine whether the user's role for the store is in the provided list.
     *
     * @param  array<int, StoreUserRole>  $roles
     */
    protected function hasRole(User $user, ?int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    protected function isOwner(User $user, ?int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner]);
    }

    protected function isOwnerOrAdmin(User $user, ?int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    protected function isOwnerAdminOrStaff(User $user, ?int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    protected function isAnyRole(User $user, ?int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    /**
     * Resolve the id of the current store bound in the container, if any.
     */
    protected function currentStoreId(): ?int
    {
        return app()->bound('current_store') ? app('current_store')->getKey() : null;
    }
}
