<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for refunds based on the user's store role.
 *
 * Refund/Order models are introduced in Phase 5. `create` receives the parent
 * Order (the refund does not exist yet). Method parameters are untyped until
 * the models exist.
 */
class RefundPolicy
{
    use ChecksStoreRole;

    /**
     * Create a refund for an order (Owner or Admin).
     */
    public function create(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }
}
