<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksStoreRole
{
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $store = $user->stores()->where('stores.id', $storeId)->first();

        if (! $store) {
            return null;
        }

        /** @var string $role */
        $role = $store->pivot->getAttribute('role');

        return StoreUserRole::from($role);
    }

    /**
     * @param  array<int, StoreUserRole>  $roles
     */
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

    protected function resolveStoreId(?Model $model = null): ?int
    {
        if ($model && $model->getAttribute('store_id')) {
            /** @var int $storeId */
            $storeId = $model->getAttribute('store_id');

            return $storeId;
        }

        if (app()->bound('current_store')) {
            /** @var Store $store */
            $store = app('current_store');

            return $store->id;
        }

        return null;
    }
}
