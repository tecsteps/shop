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
        return $this->isOwnerOrAdmin($user, (int) $store->getKey());
    }

    public function updateSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $store->getKey());
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->hasRole($user, (int) $store->getKey(), [\App\Enums\StoreUserRole::Owner]);
    }
}
