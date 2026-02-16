<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends StoreResourcePolicy
{
    public function restore(User $user, Product $product): bool
    {
        return $this->isOwnerOrAdmin($user, $product->store_id);
    }
}
