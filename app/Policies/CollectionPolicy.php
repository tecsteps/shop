<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class CollectionPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']); }
    public function view(User $user, Collection $collection): bool { return $this->hasRole($user, $collection, ['owner', 'admin', 'staff', 'support']); }
    public function create(User $user): bool { return $this->hasRole($user, null, ['owner', 'admin', 'staff']); }
    public function update(User $user, Collection $collection): bool { return $this->hasRole($user, $collection, ['owner', 'admin', 'staff']); }
    public function delete(User $user, Collection $collection): bool { return $this->hasRole($user, $collection, ['owner', 'admin']); }
}
