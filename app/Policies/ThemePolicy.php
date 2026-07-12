<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class ThemePolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin']); }
    public function view(User $user, Theme $theme): bool { return $this->hasRole($user, $theme, ['owner', 'admin']); }
    public function create(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin']); }
    public function update(User $user, Theme $theme): bool { return $this->hasRole($user, $theme, ['owner', 'admin']); }
    public function delete(User $user, Theme $theme): bool { return $this->hasRole($user, $theme, ['owner', 'admin']); }
}
