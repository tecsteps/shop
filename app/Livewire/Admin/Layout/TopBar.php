<?php

namespace App\Livewire\Admin\Layout;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class TopBar extends Component
{
    use UsesAdminStore;

    public function switchStore(int $storeId): mixed
    {
        $store = $this->currentUser()
            ->stores()
            ->whereKey($storeId)
            ->firstOrFail();

        session()->put('current_store_id', $store->id);

        return $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function logout(): mixed
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect(route('admin.login'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.layout.top-bar', [
            'currentStoreName' => $this->currentStore()->name,
            'stores' => $this->stores(),
            'unreadNotificationCount' => 0,
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * @return Collection<int, Store>
     */
    private function stores(): Collection
    {
        return $this->currentUser()
            ->stores()
            ->orderBy('stores.name')
            ->get();
    }
}
