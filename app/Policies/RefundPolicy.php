<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }
}
