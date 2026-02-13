<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any refunds.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can view the refund.
     */
    public function view(User $user): bool
    {
        return $this->hasAnyRole($user);
    }

    /**
     * Determine whether the user can create (process) refunds.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
