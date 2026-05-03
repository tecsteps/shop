<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class CustomerPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, $customer->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, $customer->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, $customer->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
