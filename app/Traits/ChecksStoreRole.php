<?php

namespace App\Traits;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksStoreRole
{
    public function getStoreRole(User $user, int $storeId): ?StoreUserRole
    {
        if (! $user->exists) {
            return null;
        }

        return StoreUser::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->getKey())
            ->first()?->role;
    }

    /**
     * @param  array<StoreUserRole|string>  $roles
     */
    public function hasRole(User $user, int $storeId, array $roles): bool
    {
        $userRole = $this->getStoreRole($user, $storeId);

        if ($userRole === null) {
            return false;
        }

        foreach ($roles as $role) {
            $role = $role instanceof StoreUserRole ? $role : StoreUserRole::tryFrom((string) $role);

            if ($role === $userRole) {
                return true;
            }
        }

        return false;
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
        $binding = (string) config('tenancy.binding', 'current_store');

        if (! app()->bound($binding)) {
            return null;
        }

        $store = app($binding);

        return $store instanceof Store ? (int) $store->getKey() : null;
    }

    /**
     * @param  array<StoreUserRole|string>  $roles
     */
    protected function userHasCurrentStoreRole(User $user, array $roles): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->hasRole($user, $storeId, $roles);
    }

    /**
     * @param  array<StoreUserRole|string>  $roles
     */
    protected function userHasModelStoreRole(User $user, Model $model, array $roles): bool
    {
        $storeId = $model instanceof Store
            ? $model->getKey()
            : $model->getAttribute('store_id');

        return $storeId !== null && $this->hasRole($user, (int) $storeId, $roles);
    }
}
