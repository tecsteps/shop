<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Discount;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Discount $discount): bool
    {
        return $this->userHasModelStoreRole($user, $discount, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Discount $discount): bool
    {
        return $this->userHasModelStoreRole($user, $discount, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $this->userHasModelStoreRole($user, $discount, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
