<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function view(User $user, object $theme): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, object $theme): bool
    {
        return $this->viewAny($user);
    }

    public function publish(User $user, object $theme): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, object $theme): bool
    {
        return $this->viewAny($user);
    }
}
