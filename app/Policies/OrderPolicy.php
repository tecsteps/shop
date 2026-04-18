<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ResolvesCurrentStore;

class OrderPolicy
{
    use ResolvesCurrentStore;

    public function viewAny(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        $role = $this->roleFor($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true);
    }

    public function refund(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
