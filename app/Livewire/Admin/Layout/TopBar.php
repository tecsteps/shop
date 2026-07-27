<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class TopBar extends Component
{
    /**
     * Count of unread notifications (no notification system yet).
     */
    public int $unreadNotificationCount = 0;

    /**
     * Switch the active store: verify membership, update the session, and
     * redirect to the dashboard (spec 03 §1.3).
     */
    public function switchStore(int $storeId): void
    {
        $user = Auth::guard('web')->user();

        $hasMembership = StoreUser::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->getKey())
            ->exists();

        abort_unless($hasMembership, 403, 'You do not have access to this store.');

        session(['current_store_id' => $storeId]);

        $this->redirect(route('admin.dashboard'));
    }

    public function render(): View
    {
        $user = Auth::guard('web')->user();

        /** @var Store $currentStore */
        $currentStore = app('current_store');

        /** @var EloquentCollection<int, Store> $stores */
        $stores = $user->stores()->orderBy('name')->get();

        return view('livewire.admin.layout.top-bar', [
            'user' => $user,
            'currentStore' => $currentStore,
            'stores' => $stores,
            'currentRole' => $user->roleForStore($currentStore),
        ]);
    }
}
