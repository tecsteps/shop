<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Database\Eloquent\Model;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Model $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
