<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class PagePolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']); }
    public function view(User $user, Page $page): bool { return $this->hasRole($user, $page, ['owner', 'admin', 'staff', 'support']); }
    public function create(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin', 'staff']); }
    public function update(User $user, Page $page): bool { return $this->hasRole($user, $page, ['owner', 'admin', 'staff']); }
    public function delete(User $user, Page $page): bool { return $this->hasRole($user, $page, ['owner', 'admin']); }
}
