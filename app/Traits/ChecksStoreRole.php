<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;

trait ChecksStoreRole
{
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $pivot = $user->stores()->where('stores.id', $storeId)->first();

        if (! $pivot) {
            return null;
        }

        $role = $pivot->pivot->role;

        return $role instanceof StoreUserRole ? $role : StoreUserRole::from($role);
    }

    /** @param  array<StoreUserRole>  $roles */
    protected function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        if (! $role) {
            return false;
        }

        return in_array($role, $roles);
    }

    protected function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    protected function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    protected function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    protected function resolveStoreId(): ?int
    {
        if (app()->bound('current_store')) {
            return app('current_store')->id;
        }

        return null;
    }
}
