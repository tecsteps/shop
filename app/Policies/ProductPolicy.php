<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ProductPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, object $product): bool
    {
        return $this->isAnyRole($user, (int) $product->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, object $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $product->store_id);
    }

    public function delete(User $user, object $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }

    public function archive(User $user, object $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }

    public function restore(User $user, object $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }
}
