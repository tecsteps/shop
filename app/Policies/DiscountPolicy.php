<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class DiscountPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Discount $discount): bool
    {
        return $this->hasRole($user, $discount, ['owner', 'admin', 'staff', 'support']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff']);
    }

    public function update(User $user, Discount $discount): bool
    {
        return $this->hasRole($user, $discount, ['owner', 'admin', 'staff']);
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $this->hasRole($user, $discount, ['owner', 'admin']);
    }
}
