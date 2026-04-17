<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;

trait ChecksStoreRole
{
    protected function getStoreRole(User $user, ?int $storeId): ?StoreUserRole
    {
        if ($storeId === null) {
            return null;
        }

        $store = \App\Models\Store::query()->find($storeId);

        if (! $store) {
            return null;
        }

        return $user->roleForStore($store);
    }

    /**
     * @param  array<StoreUserRole>  $roles
     */
    protected function hasRole(User $user, ?int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        if (! $role) {
            return false;
        }

        return in_array($role, $roles);
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

    protected function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        return app('current_store')->id;
    }
}
