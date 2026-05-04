<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function view(User $user, Refund $refund): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($refund));
    }
}
