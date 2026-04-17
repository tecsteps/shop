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
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $product->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $product->store_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }
}
