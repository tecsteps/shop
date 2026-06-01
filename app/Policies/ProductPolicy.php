<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for products based on the user's store role.
 *
 * Laravel auto-discovers this as ProductPolicy for the Product model.
 */
class ProductPolicy
{
    use ChecksStoreRole;

    /**
     * List products (any role).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    /**
     * View a product (any role).
     */
    public function view(User $user, Product $product): bool
    {
        return $this->isAnyRole($user, (int) $product->store_id);
    }

    /**
     * Create a product (Owner, Admin, or Staff).
     */
    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * Update a product (Owner, Admin, or Staff).
     */
    public function update(User $user, Product $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $product->store_id);
    }

    /**
     * Delete a product (Owner or Admin).
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }

    /**
     * Archive a product (Owner or Admin).
     */
    public function archive(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }

    /**
     * Restore a product (Owner or Admin).
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $product->store_id);
    }
}
