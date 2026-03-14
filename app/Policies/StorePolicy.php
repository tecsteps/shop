<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function view(User $user, Store $store): bool
    {
        $role = $user->roleForStore($store);

        return $role !== null;
    }

    public function update(User $user, Store $store): bool
    {
        $role = $user->roleForStore($store);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user, Store $store): bool
    {
        $role = $user->roleForStore($store);

        return $role === StoreUserRole::Owner;
    }
}
