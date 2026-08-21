<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class ProductPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Product $product): bool
    {
        return $this->userHasModelStoreRole($user, $product, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->userHasModelStoreRole($user, $product, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->userHasModelStoreRole($user, $product, [StoreUserRole::Owner, StoreUserRole::Admin]);
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
