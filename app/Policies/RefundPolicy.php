<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
