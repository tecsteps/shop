<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function update(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }
}
