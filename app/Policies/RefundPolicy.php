<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->currentStoreId());
    }
}
