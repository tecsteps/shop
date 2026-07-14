<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class ProductPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->hasRole($user, $product, ['owner', 'admin', 'staff', 'support']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff']);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->hasRole($user, $product, ['owner', 'admin', 'staff']);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->hasRole($user, $product, ['owner', 'admin']);
    }

    public function archive(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }
}
