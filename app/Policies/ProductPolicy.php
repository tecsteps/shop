<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ProductPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user);
    }

    public function view(User $user, object $product): bool
    {
        return $this->isAnyRole($user, $this->storeIdForModel($product));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    public function update(User $user, object $product): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($product));
    }

    public function delete(User $user, object $product): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($product));
    }

    public function archive(User $user, object $product): bool
    {
        return $this->delete($user, $product);
    }

    public function restore(User $user, object $product): bool
    {
        return $this->delete($user, $product);
    }
}
