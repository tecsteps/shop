<?php

namespace App\Policies\Concerns;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

trait ResolvesCurrentStore
{
    protected function roleFor(User $user): ?StoreUserRole
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $user->roleForStore($store) : null;
    }

    protected function isOwnerOrAdmin(User $user): bool
    {
        $role = $this->roleFor($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin], true);
    }

    protected function canWrite(User $user): bool
    {
        $role = $this->roleFor($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true);
    }
}
