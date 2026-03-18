<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function create(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
