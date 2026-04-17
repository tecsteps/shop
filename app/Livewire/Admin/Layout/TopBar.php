<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public function mount(): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $this->currentStoreName = $store?->name ?? 'No Store';
    }

    public function switchStore(string $storeId): void
    {
        $user = auth()->user();
        $store = Store::find($storeId);

        if ($store && $user->stores()->where('stores.id', $store->id)->exists()) {
            session(['current_store_id' => $store->id]);
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function getStoresProperty(): Collection
    {
        return auth()->user()->stores()->get();
    }

    public function render()
    {
        return view('livewire.admin.layout.top-bar');
    }
}
