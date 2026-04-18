<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function view(User $user, Store $store): bool
    {
        return $user->roleForStore($store) !== null;
    }

    public function update(User $user, Store $store): bool
    {
        return in_array($user->roleForStore($store), [StoreUserRole::Owner, StoreUserRole::Admin], true);
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->roleForStore($store) === StoreUserRole::Owner;
    }

    public function manageStaff(User $user, Store $store): bool
    {
        return $this->update($user, $store);
    }
}
