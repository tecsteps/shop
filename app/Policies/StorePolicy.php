<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    /**
     * View store settings (Owner or Admin).
     */
    public function viewSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->id);
    }

    /**
     * Update store settings (Owner or Admin).
     */
    public function updateSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->id);
    }

    /**
     * Delete the store (Owner only).
     */
    public function delete(User $user, Store $store): bool
    {
        return $this->isOwner($user, $store->id);
    }
}
