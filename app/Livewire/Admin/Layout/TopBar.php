<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopBar extends Component
{
    public int $unreadNotificationCount = 0;

    public function switchStore(int $storeId): void
    {
        $store = Auth::user()?->stores()->where('stores.id', $storeId)->firstOrFail();
        session()->put('current_store_id', $store->id);
        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render(): View
    {
        /** @var Collection<int, Store> $stores */
        $stores = Auth::user()?->stores()->orderBy('name')->get() ?? new Collection;

        return view('livewire.admin.layout.top-bar', [
            'stores' => $stores,
            'currentStoreName' => app('current_store')->name,
        ]);
    }
}
