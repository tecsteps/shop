<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for collections based on the user's store role.
 *
 * The Collection model is introduced in Phase 2. Method parameters are untyped
 * until the model exists; Laravel auto-discovers this as CollectionPolicy.
 */
class CollectionPolicy
{
    use ChecksStoreRole;

    /**
     * List collections (any role).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    /**
     * View a collection (any role).
     */
    public function view(User $user, object $collection): bool
    {
        return $this->isAnyRole($user, (int) $collection->store_id);
    }

    /**
     * Create a collection (Owner, Admin, or Staff).
     */
    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * Update a collection (Owner, Admin, or Staff).
     */
    public function update(User $user, object $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $collection->store_id);
    }

    /**
     * Delete a collection (Owner or Admin).
     */
    public function delete(User $user, object $collection): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $collection->store_id);
    }
}
