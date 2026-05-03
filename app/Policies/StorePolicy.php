<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class StorePolicy
{
    use ChecksStoreRoles;

    public function view(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, [StoreUserRole::Owner]);
    }
}
