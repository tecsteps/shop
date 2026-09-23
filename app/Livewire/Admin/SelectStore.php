<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SelectStore extends Component
{
    public Collection $stores;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);
        $this->stores = $user->stores()->orderBy('stores.name')->get(['stores.id', 'stores.name']);

        if ($this->stores->isEmpty()) {
            abort(403);
        }

        session()->forget('current_store_id');
    }

    public function select(string $storeId): mixed
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
        return view('livewire.admin.select-store')->layout('layouts.auth');
    }
}
