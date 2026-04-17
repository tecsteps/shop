<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any pages.
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can view the page.
     */
    public function view(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can create pages.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can update the page.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    /**
     * Determine whether the user can delete the page.
     */
    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
