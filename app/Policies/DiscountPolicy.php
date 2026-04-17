<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class DiscountPolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->getStoreId());
    }

    public function view(User $user, $discount): bool
    {
        return $this->isAnyRole($user, $discount->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->getStoreId());
    }

    public function update(User $user, $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, $discount->store_id);
    }

    public function delete(User $user, $discount): bool
    {
        return $this->isOwnerOrAdmin($user, $discount->store_id);
    }
}
