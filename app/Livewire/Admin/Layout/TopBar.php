<?php

namespace App\Livewire\Admin\Layout;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class TopBar extends Component
{
    /**
     * Switch the active store in the session and reload the dashboard so
     * every store-scoped query rebinds to the newly selected store.
     */
    public function switchStore(int $storeId): void
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->stores()->whereKey($storeId)->exists(), 403);

        session(['current_store_id' => $storeId]);

        $this->redirect(route('admin.dashboard'));
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.admin.layout.top-bar', [
            'stores' => $user->stores()->orderBy('name')->get(),
        ]);
    }
}
