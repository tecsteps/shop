<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Theme;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class ThemePolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function view(User $user, Theme $theme): bool
    {
        return $this->hasRole($user, $theme->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function update(User $user, Theme $theme): bool
    {
        return $this->view($user, $theme);
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $this->view($user, $theme);
    }
}
