<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    public function getStoreRole(User $user, ?int $storeId = null): ?StoreUserRole
    {
        $storeId ??= $this->currentStoreId();

        if (! $storeId) {
            return null;
        }

        return $user->roleForStoreId($storeId);
    }

    /**
     * @param  array<int, StoreUserRole>  $roles
     */
    public function hasRole(User $user, ?int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        return $role !== null && in_array($role, $roles, true);
    }

    public function isOwnerOnly(User $user, ?int $storeId = null): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner]);
    }

    public function isOwnerOrAdmin(User $user, ?int $storeId = null): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function isOwnerAdminOrStaff(User $user, ?int $storeId = null): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function isAnyRole(User $user, ?int $storeId = null): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    protected function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store->getKey() : null;
    }

    protected function storeIdForModel(object $model): ?int
    {
        if (method_exists($model, 'getAttribute')) {
            $storeId = $model->getAttribute('store_id');

            return is_numeric($storeId) ? (int) $storeId : null;
        }

        if (property_exists($model, 'store_id')) {
            return is_numeric($model->store_id) ? (int) $model->store_id : null;
        }

        return null;
    }
}
