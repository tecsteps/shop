<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
