<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use App\Traits\ChecksStoreRole;

/**
 * Authorization for CMS pages based on the user's store role.
 *
 * Laravel auto-discovers this as the policy for {@see Page}.
 */
class PagePolicy
{
    use ChecksStoreRole;

    /**
     * List pages (Owner, Admin, or Staff).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * View a page (Owner, Admin, or Staff).
     */
    public function view(User $user, Page $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $page->store_id);
    }

    /**
     * Create a page (Owner, Admin, or Staff).
     */
    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    /**
     * Update a page (Owner, Admin, or Staff).
     */
    public function update(User $user, Page $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $page->store_id);
    }

    /**
     * Delete a page (Owner or Admin).
     */
    public function delete(User $user, Page $page): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $page->store_id);
    }
}
