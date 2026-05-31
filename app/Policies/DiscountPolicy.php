<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for discounts based on the user's store role.
 *
 * The Discount model is introduced in Phase 4. Method parameters are untyped
 * until the model exists; Laravel auto-discovers this as DiscountPolicy.
 */
class DiscountPolicy
{
    use ChecksStoreRole;

    /**
     * List discounts (any role).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    /**
     * View a discount (any role).
     */
    public function view(User $user, object $discount): bool
    {
        return $this->isAnyRole($user, (int) $discount->store_id);
    }

    /**
     * Create a discount (Owner, Admin, or Staff).
     */
    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * Update a discount (Owner, Admin, or Staff).
     */
    public function update(User $user, object $discount): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $discount->store_id);
    }

    /**
     * Delete a discount (Owner or Admin).
     */
    public function delete(User $user, object $discount): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $discount->store_id);
    }
}
