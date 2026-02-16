<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;

trait ChecksStoreRole
{
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $pivot = $user->stores()->where('stores.id', $storeId)->first()?->pivot;

        if (! $pivot) {
            return null;
        }

        return StoreUserRole::from($pivot->role);
    }

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

    protected function resolveStoreId($model = null): ?int
    {
        if ($model && isset($model->store_id)) {
            return $model->store_id;
        }

        if (app()->bound('current_store')) {
            return app('current_store')->id;
        }

        return null;
    }
}
