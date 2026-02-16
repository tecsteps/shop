<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class ProductPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isAnyRole($user, $product->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $product->store_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }
}
