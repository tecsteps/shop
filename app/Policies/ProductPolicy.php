<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesCurrentStore;

class ProductPolicy
{
    use ResolvesCurrentStore;

    public function viewAny(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }
}
