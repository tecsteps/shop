<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for themes based on the user's store role.
 *
 * Laravel auto-discovers this as the policy for {@see Theme}.
 */
class ThemePolicy
{
    use ChecksStoreRole;

    /**
     * List themes (Owner or Admin).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    /**
     * View a theme (Owner or Admin).
     */
    public function view(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    /**
     * Create a theme (Owner or Admin).
     */
    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    /**
     * Update a theme (Owner or Admin).
     */
    public function update(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    /**
     * Delete a theme (Owner or Admin).
     */
    public function delete(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    /**
     * Publish a theme (Owner or Admin).
     */
    public function publish(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }
}
