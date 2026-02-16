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
        return $this->isAnyRole($user, $store->id);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->id);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store->id, [\App\Enums\StoreUserRole::Owner]);
    }
}
