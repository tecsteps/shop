<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    /**
     * Get the user's role for the given store.
     */
    protected function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        /** @var Store|null $store */
        $store = $user->stores()
            ->where('stores.id', $storeId)
            ->first();

        if (! $store) {
            return null;
        }

        $pivot = $store->pivot;
        $role = $pivot->role;

        return $role instanceof StoreUserRole
            ? $role
            : StoreUserRole::from((string) $role);
    }

    /**
     * Check if the user has one of the given roles for the store.
     *
     * @param  array<StoreUserRole>  $roles
     */
    protected function hasRole(User $user, int $storeId, array $roles): bool
    {
        $role = $this->getStoreRole($user, $storeId);

        if (! $role) {
            return false;
        }

        return in_array($role, $roles);
    }

    /**
     * Check if the user is an Owner or Admin for the store.
     */
    protected function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
        ]);
    }

    /**
     * Check if the user is an Owner, Admin, or Staff for the store.
     */
    protected function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
        ]);
    }

    /**
     * Check if the user has any role at all for the store.
     */
    protected function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    /**
     * Resolve the store ID from a model or the current store.
     */
    protected function resolveStoreId(?object $model = null): int
    {
        if ($model instanceof Store) {
            return (int) $model->id;
        }

        if ($model && property_exists($model, 'store_id')) {
            return (int) $model->store_id; // @phpstan-ignore property.nonObject
        }

        /** @var Store $currentStore */
        $currentStore = app('current_store');

        return (int) $currentStore->id;
    }
}
