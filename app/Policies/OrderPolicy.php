<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can manage orders (create, update).
     */
    public function manage(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }
}
