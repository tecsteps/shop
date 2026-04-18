<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesCurrentStore;

class DiscountPolicy
{
    use ResolvesCurrentStore;

    public function viewAny(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function manage(User $user): bool
    {
        return $this->canWrite($user);
    }
}
