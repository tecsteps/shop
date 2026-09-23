<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product) && $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product) && $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product) && $this->hasRole($user, ['owner', 'admin']);
    }

    private function ownsProduct(User $user, Product $product): bool
    {
        return $product->store_id === (int) (app()->bound('current_store') ? app('current_store')->id : 0)
            && $user->roleForStore(app('current_store')) !== null;
    }

    private function hasRole(User $user, array $roles): bool
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store instanceof Store && in_array($user->roleForStore($store), $roles, true);
    }
}
