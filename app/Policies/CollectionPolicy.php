<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any collections.
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can view the collection.
     */
    public function view(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can create collections.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can update the collection.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can delete the collection.
     */
    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
