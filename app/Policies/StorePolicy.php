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

    /**
     * View analytics (spec 05 section 2 role matrix: Owner, Admin, and
     * Staff may view analytics; Support may not).
     */
    public function viewAnalytics(User $user, Store $store): bool
    {
        return $this->isOwnerAdminOrStaff($user, $store->getKey());
    }

    public function updateSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->getKey());
    }

    /**
     * Create and revoke API tokens (spec 06 section 2.3: manage-developers
     * is granted to Owner and Admin).
     */
    public function manageDevelopers(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store->getKey());
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->isOwner($user, $store->getKey());
    }
}
