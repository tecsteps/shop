<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    public function view(User $user, Store $store): bool
    {
        return $this->isAnyRole($user, $store->getKey());
    }

    public function update(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->getKey());
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->isOwnerOnly($user, $store->getKey());
    }
}
