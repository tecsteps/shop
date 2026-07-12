<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class NavigationMenuPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff']);
    }

    public function manage(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin']);
    }
}
