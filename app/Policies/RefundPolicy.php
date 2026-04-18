<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesCurrentStore;

class RefundPolicy
{
    use ResolvesCurrentStore;

    public function viewAny(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
