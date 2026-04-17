<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;

class RefundPolicy
{
    public function create(User $user): bool
    {
        $role = $this->getRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    private function getRole(User $user): ?StoreUserRole
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store ? $user->roleForStore($store) : null;
    }
}
