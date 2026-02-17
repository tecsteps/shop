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
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId($product));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId($product));
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId($product));
    }

    public function archive(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId($product));
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId($product));
    }
}
