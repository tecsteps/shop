<?php

namespace App\Livewire\Admin;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Number;
use Livewire\Component;

abstract class AdminComponent extends Component
{
    public function currentStore(): Store
    {
        if (app()->bound('current_store')) {
            return app('current_store');
        }

        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        $store = $user->stores()
            ->when(session('current_store_id'), fn ($query) => $query->whereKey(session('current_store_id')))
            ->first() ?? $user->stores()->first();

        abort_unless($store instanceof Store, 403);

        return $store;
    }

    /** @param list<StoreUserRole> $roles */
    protected function authorizeStore(array $roles = [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 401);
        abort_unless(in_array($user->roleForStore($this->currentStore()), $roles, true), 403);
    }

    public function currency(int $amount, ?string $currency = null): string
    {
        return Number::currency($amount / 100, in: $currency ?? $this->currentStore()->default_currency);
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }
}
