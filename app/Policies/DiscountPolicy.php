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
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Discount $discount): bool
    {
        return $this->isAnyRole($user, $discount->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
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
