<?php

namespace App\Livewire\Admin\Layout;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use Livewire\Component;

class TopBar extends Component
{
    use ResolvesAdminStore;

    public function switchStore(int $storeId): mixed
    {
        $user = auth()->user();

        abort_if($user === null, 403);

        $isMember = $user->stores()->where('stores.id', $storeId)->exists();

        abort_if(! $isMember, 403);

        session()->put('current_store_id', $storeId);

        return redirect()->route('admin.dashboard');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.admin.layout.top-bar', [
            'currentStore' => $this->store(),
            'stores' => $user ? $user->stores()->orderBy('name')->get() : collect(),
            'user' => $user,
        ]);
    }
}
