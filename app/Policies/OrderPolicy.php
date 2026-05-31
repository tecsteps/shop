<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for orders based on the user's store role.
 *
 * The Order model is introduced in Phase 5. Method parameters are untyped until
 * the model exists; Laravel auto-discovers this as OrderPolicy.
 */
class OrderPolicy
{
    use ChecksStoreRole;

    /**
     * List orders (any role).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    /**
     * View an order (any role).
     */
    public function view(User $user, object $order): bool
    {
        return $this->isAnyRole($user, (int) $order->store_id);
    }

    /**
     * Update an order (Owner, Admin, or Staff).
     */
    public function update(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    /**
     * Cancel an order (Owner or Admin).
     */
    public function cancel(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }

    /**
     * Create a fulfillment for an order (Owner, Admin, or Staff).
     */
    public function createFulfillment(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    /**
     * Create a refund for an order (Owner or Admin).
     */
    public function createRefund(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }
}
