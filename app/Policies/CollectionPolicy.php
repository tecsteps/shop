<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    public function view(User $user, Collection $collection): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId($collection));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function update(User $user, Collection $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId($collection));
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId($collection));
    }
}
