<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        $store = session('current_store');
        $this->currentStoreName = $store?->name ?? 'Select Store';
    }

    public function getStoresProperty(): Collection
    {
        $user = Auth::user();

        return $user ? $user->stores : collect();
    }

    public function switchStore(int $storeId): void
    {
        $store = Store::withoutGlobalScopes()->findOrFail($storeId);

        session(['current_store' => $store, 'store_id' => $store->id]);
        $this->currentStoreName = $store->name;

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.layout.top-bar');
    }
}
