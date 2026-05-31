<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for navigation menus based on the user's store role.
 *
 * The NavigationMenu model is introduced in Phase 3. Method parameters are
 * untyped until the model exists; Laravel auto-discovers this as
 * NavigationMenuPolicy.
 */
class NavigationMenuPolicy
{
    use ChecksStoreRole;

    /**
     * List navigation menus (Owner, Admin, or Staff).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * Manage navigation menus (Owner or Admin).
     */
    public function manage(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }
}
