<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Database\Eloquent\Model;

abstract class StoreResourcePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Model $model): bool
    {
        /** @var int $storeId */
        $storeId = $model->getAttribute('store_id');

        return $this->isAnyRole($user, $storeId);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var int $storeId */
        $storeId = $model->getAttribute('store_id');

        return $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var int $storeId */
        $storeId = $model->getAttribute('store_id');

        return $this->isOwnerOrAdmin($user, $storeId);
    }
}
