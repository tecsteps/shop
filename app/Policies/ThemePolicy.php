<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    /**
     * Determine whether the user can view any themes.
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can view the theme.
     */
    public function view(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can create themes.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can update the theme.
     */
    public function update(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can delete the theme.
     */
    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
