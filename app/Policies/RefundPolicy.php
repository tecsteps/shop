<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user, object $refund): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
