<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;

trait ChecksStoreRole
{
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        return $user->storeUsers()
            ->where('store_id', $storeId)
            ->first()
            ?->role;
    }

    /** @param list<StoreUserRole> $roles */
    protected function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    protected function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
        ]);
    }

    protected function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
        ]);
    }

    protected function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }
}
