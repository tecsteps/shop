<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Theme;
use App\Models\User;

class ThemePolicy
{
    public function viewAny(User $user): bool
    {
        $role = $this->getRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function view(User $user, Theme $theme): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Theme $theme): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $this->viewAny($user);
    }

    private function getRole(User $user): ?StoreUserRole
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store ? $user->roleForStore($store) : null;
    }
}
