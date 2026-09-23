<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\Store;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->belongsToCurrentStore($page) && $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->belongsToCurrentStore($page) && $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->belongsToCurrentStore($page) && $this->hasRole($user, ['owner', 'admin']);
    }

    private function belongsToCurrentStore(Page $page): bool
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store instanceof Store && (int) $page->store_id === (int) $store->getKey();
    }

    /** @param list<string> $roles */
    private function hasRole(User $user, array $roles): bool
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store instanceof Store && in_array($user->roleForStore($store), $roles, true);
    }
}
