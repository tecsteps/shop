<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    public function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $pivot = $user->stores()
            ->wherePivot('store_id', $storeId)
            ->first()?->pivot;

        if ($pivot === null) {
            return null;
        }

        $role = $pivot->role;

        return $role instanceof StoreUserRole ? $role : StoreUserRole::tryFrom((string) $role);
    }

    /**
     * @param  array<int, StoreUserRole>  $roles
     */
    public function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    public function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
        ]);
    }

    public function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    protected function resolveCurrentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? (int) $store->getKey() : null;
    }
}
