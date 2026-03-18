<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    public function manageSettings(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner]);
    }

    public function manageStaff(User $user): bool
    {
        return $this->hasStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
