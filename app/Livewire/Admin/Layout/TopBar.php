<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Support\Collection;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    /** @var Collection<int, Store> */
    public Collection $stores;

    public function mount(): void
    {
        $user = auth()->user();
        $this->stores = $user ? $user->stores : collect();

        $currentStore = app('current_store');
        $this->currentStoreName = $currentStore?->name ?? 'No Store';
    }

    public function switchStore(int $storeId): void
    {
        $user = auth()->user();

        if (! $user || ! $user->stores()->where('stores.id', $storeId)->exists()) {
            return;
        }

        session(['current_store_id' => $storeId]);

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.layout.top-bar');
    }
}
