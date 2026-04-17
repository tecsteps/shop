<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function view(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function update(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }

    public function publish(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }
}
