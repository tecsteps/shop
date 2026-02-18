<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ProductPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Product  $product
     */
    public function view(User $user, $product): bool
    {
        return $this->isAnyRole($user, $product->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Product  $product
     */
    public function update(User $user, $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $product->store_id);
    }

    /**
     * @param  \App\Models\Product  $product
     */
    public function delete(User $user, $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }

    /**
     * @param  \App\Models\Product  $product
     */
    public function archive(User $user, $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }

    /**
     * @param  \App\Models\Product  $product
     */
    public function restore(User $user, $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }
}
