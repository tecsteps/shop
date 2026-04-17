<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ChecksStoreRole
{
    protected function getUserRole(User $user): ?StoreUserRole
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        /** @var Store $store */
        $store = app('current_store');

        return $user->roleForStore($store);
    }

    protected function hasMinRole(User $user, StoreUserRole ...$roles): bool
    {
        $role = $this->getUserRole($user);

        return $role !== null && in_array($role, $roles, true);
    }
}
