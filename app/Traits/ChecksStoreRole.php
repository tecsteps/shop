<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;

trait ChecksStoreRole
{
    public function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $store = $user->stores()
            ->where('stores.id', $storeId)
            ->first();

        if (! $store) {
            return null;
        }

        /** @var StoreUser|null $pivot */
        $pivot = $store->getRelation('pivot');

        return $pivot?->role;
    }

    /**
     * @param  array<StoreUserRole>  $roles
     */
    public function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        if (! $role) {
            return false;
        }

        return in_array($role, $roles, true);
    }

    public function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
        ]);
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

    protected function resolveStoreId(): int
    {
        /** @var Store $store */
        $store = app('current_store');

        return $store->id;
    }
}
