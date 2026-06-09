<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    public function viewSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->getKey());
    }

    public function updateSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->getKey());
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->isOwner($user, $store->getKey());
    }
}
