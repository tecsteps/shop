<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRoles
{
    /**
     * @param  list<StoreUserRole>  $roles
     */
    protected function hasAnyRole(User $user, array $roles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        return $this->hasRole($user, app('current_store'), $roles);
    }

    /**
     * @param  list<StoreUserRole>  $roles
     */
    protected function hasRole(User $user, Store $store, array $roles): bool
    {
        return in_array($user->roleForStore($store), $roles, true);
    }
}
