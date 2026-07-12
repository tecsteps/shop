<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        $this->currentStoreName = (string) app('current_store')->name;
    }

    public function switchStore(string $storeId): void
    {
        $allowed = StoreUser::query()->where('user_id', Auth::id())->where('store_id', $storeId)->exists();
        abort_unless($allowed, 403);

        session(['current_store_id' => (int) $storeId]);
        $this->redirect('/admin', navigate: true);
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect('/admin/login', navigate: true);
    }

    /** @return Collection<int, Store> */
    public function getStoresProperty(): Collection
    {
        return Auth::user()?->stores()->orderBy('name')->get() ?? collect();
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('admin.layout.top-bar', [
            'stores' => $this->stores,
            'user' => $user,
            'role' => $user?->roleForStore(app('current_store'))?->value,
        ]);
    }
}
