<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, object $discount): bool
    {
        return $this->isAnyRole($user, (int) $discount->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, object $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $discount->store_id);
    }

    public function delete(User $user, object $discount): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $discount->store_id);
    }
}
