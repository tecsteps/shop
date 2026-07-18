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
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user, object $order): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, object $order): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, object $order): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function refund(User $user, object $order): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function fulfill(User $user, object $order): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }
}
