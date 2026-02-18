<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Discount  $discount
     */
    public function view(User $user, $discount): bool
    {
        return $this->isAnyRole($user, $discount->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Discount  $discount
     */
    public function update(User $user, $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, $discount->store_id);
    }

    /**
     * @param  \App\Models\Discount  $discount
     */
    public function delete(User $user, $discount): bool
    {
        return $this->isOwnerOrAdmin($user, $discount->store_id);
    }
}
