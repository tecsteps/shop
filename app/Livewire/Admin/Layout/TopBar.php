<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        $this->currentStoreName = app('current_store')->name;
    }

    /**
     * Stores the current user has access to, for the store switcher.
     */
    public function getStoresProperty(): Collection
    {
        return auth()->user()->stores;
    }

    /**
     * Switch the active store in the session and return to the dashboard.
     */
    public function switchStore(string $storeId): void
    {
        $store = Store::find($storeId);

        if (! $store || ! auth()->user()->stores()->whereKey($store->id)->exists()) {
            return;
        }

        session(['current_store_id' => $store->id]);

        $this->redirect(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.layout.top-bar');
    }
}
