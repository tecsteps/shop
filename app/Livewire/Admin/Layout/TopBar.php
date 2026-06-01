<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The admin top bar: mobile sidebar toggle, store selector, and user profile
 * menu. The store selector lists every store the authenticated user belongs to
 * and switches the session's active store.
 */
class TopBar extends Component
{
    public int $unreadNotificationCount = 0;

    /**
     * The name of the currently active store.
     */
    public function getCurrentStoreNameProperty(): string
    {
        return app()->bound('current_store')
            ? app('current_store')->name
            : '';
    }

    /**
     * The stores the authenticated user has access to.
     *
     * @return Collection<int, Store>
     */
    public function getStoresProperty(): Collection
    {
        $user = Auth::guard('web')->user();

        if ($user === null) {
            return collect();
        }

        return $user->stores()->withoutGlobalScopes()->orderBy('name')->get();
    }

    /**
     * Switch the active store in the session and reload the dashboard.
     */
    public function switchStore(int $storeId): mixed
    {
        $user = Auth::guard('web')->user();

        $store = $user?->stores()->withoutGlobalScopes()->whereKey($storeId)->first();

        if ($store === null) {
            return null;
        }

        session()->put('current_store_id', $store->id);

        return $this->redirectRoute('admin.dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.layout.top-bar');
    }
}
