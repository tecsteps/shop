<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Discount;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class DiscountPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user, Discount $discount): bool
    {
        return $this->hasRole($user, $discount->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Discount $discount): bool
    {
        return $this->view($user, $discount);
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $this->hasRole($user, $discount->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
