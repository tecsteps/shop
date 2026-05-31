<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for fulfillments based on the user's store role.
 *
 * Fulfillment/Order models are introduced in Phase 5. `create` receives the
 * parent Order (the fulfillment does not exist yet); `update`/`cancel` navigate
 * through the fulfillment's order relationship to obtain the store_id. Method
 * parameters are untyped until the models exist.
 */
class FulfillmentPolicy
{
    use ChecksStoreRole;

    /**
     * Create a fulfillment for an order (Owner, Admin, or Staff).
     */
    public function create(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    /**
     * Update a fulfillment (Owner, Admin, or Staff).
     */
    public function update(User $user, object $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdFor($fulfillment));
    }

    /**
     * Cancel a fulfillment (Owner, Admin, or Staff).
     */
    public function cancel(User $user, object $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdFor($fulfillment));
    }

    /**
     * Resolve the store id for a fulfillment via its order relationship.
     */
    private function storeIdFor(object $fulfillment): int
    {
        if (isset($fulfillment->order) && isset($fulfillment->order->store_id)) {
            return (int) $fulfillment->order->store_id;
        }

        return (int) ($fulfillment->store_id ?? 0);
    }
}
