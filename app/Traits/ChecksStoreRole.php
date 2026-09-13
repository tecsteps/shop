<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Tenancy\CurrentStore;

/**
 * Role-checking helpers for store-scoped authorization.
 * Included in all policies and used by gate definitions.
 */
trait ChecksStoreRole
{
    public function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        $role = $user->stores()->where('stores.id', $storeId)->first()?->pivot->role;

        return $role instanceof StoreUserRole ? $role : null;
    }

    /**
     * @param  array<int, StoreUserRole>  $roles
     */
    public function hasRole(User $user, int $storeId, array $roles): bool
    {
        return in_array($this->getStoreRole($user, $storeId), $roles, true);
    }

    public function isOwnerOrAdmin(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function isOwnerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->hasRole($user, $storeId, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function isAnyRole(User $user, int $storeId): bool
    {
        return $this->getStoreRole($user, $storeId) !== null;
    }

    protected function currentStoreId(): ?int
    {
        $current = app(CurrentStore::class);

        return $current->id() ?? $current->adminIdFromSession();
    }
}
