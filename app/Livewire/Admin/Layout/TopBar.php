<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    /** @var Collection<int, Store> */
    public Collection $stores;

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->stores = $user ? $user->stores : collect();

        $store = app('current_store');
        $this->currentStoreName = $store ? $store->name : 'No Store';
    }

    public function switchStore(int $storeId): void
    {
        $user = Auth::user();
        $store = $user->stores()->where('stores.id', $storeId)->first();

        if ($store) {
            session(['current_store_id' => $store->id]);
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.admin.layout.top-bar');
    }
}
