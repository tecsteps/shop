<?php

namespace App\Traits;

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

    protected function isOwner(User $user): bool
    {
        return $this->getUserRole($user) === StoreUserRole::Owner;
    }

    protected function isAdmin(User $user): bool
    {
        return in_array($this->getUserRole($user), [StoreUserRole::Owner, StoreUserRole::Admin], true);
    }

    protected function isStaff(User $user): bool
    {
        return in_array($this->getUserRole($user), [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
        ], true);
    }

    protected function isSupport(User $user): bool
    {
        return $this->getUserRole($user) !== null;
    }
}
