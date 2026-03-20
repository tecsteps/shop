<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopBar extends Component
{
    public function switchStore(int $storeId): mixed
    {
        $user = Auth::user();

        $hasAccess = $user->stores()->where('stores.id', $storeId)->exists();

        if (! $hasAccess) {
            return null;
        }

        session()->put('current_store_id', $storeId);

        return redirect()->route('admin.dashboard');
    }

    public function logout(): mixed
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function render(): mixed
    {
        $user = Auth::user();
        $stores = $user ? $user->stores : collect();
        $currentStore = app()->bound('current_store') ? app('current_store') : null;

        return view('livewire.admin.layout.top-bar', [
            'user' => $user,
            'stores' => $stores,
            'currentStore' => $currentStore,
        ]);
    }
}
