<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user);
    }

    public function view(User $user, object $collection): bool
    {
        return $this->isAnyRole($user, $this->storeIdForModel($collection));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    public function update(User $user, object $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($collection));
    }

    public function delete(User $user, object $collection): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($collection));
    }
}
