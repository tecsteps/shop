<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isSupport($user);
    }

    public function view(User $user): bool
    {
        return $this->isSupport($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user): bool
    {
        return $this->isStaff($user);
    }
}
