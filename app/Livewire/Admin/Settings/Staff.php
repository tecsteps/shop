<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreUserRole;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Staff extends Component
{
    public bool $showInviteModal = false;

    public string $inviteEmail = '';

    public string $inviteRole = 'staff';

    public function mount(): void
    {
        $store = app('current_store');
        $this->authorize('viewSettings', $store);
    }

    public function openInvite(): void
    {
        $this->showInviteModal = true;
    }

    public function invite(): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        $this->validate([
            'inviteEmail' => ['required', 'email'],
            'inviteRole' => ['required', 'in:'.implode(',', StoreUserRole::values())],
        ]);

        $user = User::query()->where('email', $this->inviteEmail)->first();

        if ($user !== null && ! $user->stores()->where('store_id', $store->getKey())->exists()) {
            $user->stores()->attach($store->getKey(), [
                'role' => $this->inviteRole,
                'created_at' => now(),
            ]);
        }

        $this->reset(['showInviteModal', 'inviteEmail', 'inviteRole']);
        session()->flash('status', 'Staff invited.');
    }

    public function render(): View
    {
        $store = app('current_store');
        $members = $store->users()->get();

        return view('livewire.admin.settings.staff', [
            'members' => $members,
            'roles' => StoreUserRole::values(),
        ]);
    }
}
