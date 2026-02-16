<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PagePolicy extends StoreResourcePolicy
{
    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function view(User $user, Model $model): bool
    {
        /** @var int $storeId */
        $storeId = $model->getAttribute('store_id');

        return $this->isOwnerAdminOrStaff($user, $storeId);
    }
}
