<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    protected function currentStore(): ?Store
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store : null;
    }

    protected function role(User $user): ?StoreUserRole
    {
        $store = $this->currentStore();

        if ($store === null) {
            return null;
        }

        return $user->roleForStore($store);
    }

    /**
     * @param  list<StoreUserRole>  $allowed
     */
    protected function hasRole(User $user, array $allowed): bool
    {
        $role = $this->role($user);

        return $role !== null && in_array($role, $allowed, true);
    }
}
