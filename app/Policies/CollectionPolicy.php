<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Database\Eloquent\Model;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Model $collection): bool
    {
        return $this->isAnyRole($user, $collection->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, Model $collection): bool
    {
        return $this->isOwnerAdminOrStaff($user, $collection->store_id);
    }

    public function delete(User $user, Model $collection): bool
    {
        return $this->isOwnerOrAdmin($user, $collection->store_id);
    }
}
