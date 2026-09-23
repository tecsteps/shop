<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public ?int $currentStoreId = null;

    public Collection $stores;

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $store = app()->bound('current_store') ? app('current_store') : null;
        $this->currentStoreName = $store instanceof Store ? $store->name : 'Select a store';
        $this->currentStoreId = $store instanceof Store ? (int) $store->id : null;
        $this->stores = $user->stores()->orderBy('stores.name')->get(['stores.id', 'stores.name']);
    }

    public function switchStore(string $storeId): mixed
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $store = $user->stores()->whereKey($storeId)->first();
        abort_unless($store instanceof Store, 403);

        session()->put('current_store_id', $store->id);

        return $this->redirectRoute('admin.dashboard');
    }

    public function render(): mixed
    {
        return view('livewire.admin.layout.top-bar');
    }
}
