<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->getStoreId());
    }

    public function view(User $user, $customer): bool
    {
        return $this->isAnyRole($user, $customer->store_id);
    }

    public function update(User $user, $customer): bool
    {
        return $this->isOwnerAdminOrStaff($user, $customer->store_id);
    }
}
