<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\User;

trait ChecksStoreRole
{
    /**
     * @param  array<StoreUserRole>  $roles
     */
    protected function hasStoreRole(User $user, array $roles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        $role = $user->roleForStore(app('current_store'));

        return $role !== null && in_array($role, $roles);
    }
}
