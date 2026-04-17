<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function view(User $user, Discount $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, $discount->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function update(User $user, Discount $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, $discount->store_id);
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $this->isOwnerOrAdmin($user, $discount->store_id);
    }
}
