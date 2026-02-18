<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Collection  $collection
     */
    public function view(User $user, $collection): bool
    {
        return $this->isAnyRole($user, $collection->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Collection  $collection
     */
    public function update(User $user, $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, $collection->store_id);
    }

    /**
     * @param  \App\Models\Collection  $collection
     */
    public function delete(User $user, $collection): bool
    {
        return $this->isOwnerOrAdmin($user, $collection->store_id);
    }
}
