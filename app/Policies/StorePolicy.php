<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class StorePolicy
{
    use ChecksStoreRoles;

    public function view(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner', 'admin', 'staff', 'support']);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner', 'admin']);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner']);
    }

    public function manageStaff(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner', 'admin']);
    }

    public function viewSettings(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner', 'admin']);
    }

    public function viewAnalytics(User $user, Store $store): bool
    {
        return $this->hasRole($user, $store, ['owner', 'admin', 'staff']);
    }

    public function updateSettings(User $user, Store $store): bool
    {
        return $this->viewSettings($user, $store);
    }
}
