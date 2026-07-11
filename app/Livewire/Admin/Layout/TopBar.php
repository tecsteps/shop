<?php

namespace App\Livewire\Admin\Layout;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class TopBar extends Component
{
    public string $currentStoreName = '';

    public int $unreadNotificationCount = 0;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->currentStoreName = $user?->stores()
            ->when(session('current_store_id'), fn ($query) => $query->whereKey(session('current_store_id')))
            ->value('name') ?? 'Select store';
    }

    public function switchStore(int $storeId): void
    {
        /** @var User $user */
        $user = Auth::user();
        $store = $user->stores()->whereKey($storeId)->firstOrFail();
        Session::put('current_store_id', $store->getKey());
        $this->redirect('/admin', navigate: true);
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/admin/login', navigate: true);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.admin.layout.top-bar', [
            'stores' => $user->stores()->orderBy('name')->get(),
        ]);
    }
}
