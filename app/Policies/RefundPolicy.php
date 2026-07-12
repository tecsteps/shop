<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class RefundPolicy
{
    use ChecksStoreRoles;

    public function create(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin']);
    }

    public function view(User $user, Refund $refund): bool
    {
        return $this->hasRole($user, $refund, ['owner', 'admin', 'staff', 'support']);
    }
}
