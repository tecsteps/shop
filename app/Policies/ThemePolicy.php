<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for themes based on the user's store role.
 *
 * The Theme model is introduced in Phase 3. Method parameters are untyped until
 * the model exists; Laravel auto-discovers this as ThemePolicy.
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
    public function view(User $user, object $theme): bool
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
    public function update(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    /**
     * Delete a theme (Owner or Admin).
     */
    public function delete(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    /**
     * Publish a theme (Owner or Admin).
     */
    public function publish(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }
}
