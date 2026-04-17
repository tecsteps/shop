<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->getStoreId());
    }

    public function view(User $user, $collection): bool
    {
        return $this->isAnyRole($user, $collection->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->getStoreId());
    }

    public function update(User $user, $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, $collection->store_id);
    }

    public function delete(User $user, $collection): bool
    {
        return $this->isOwnerOrAdmin($user, $collection->store_id);
    }
}
