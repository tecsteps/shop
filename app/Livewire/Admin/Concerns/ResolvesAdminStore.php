<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Store;
use App\Tenancy\CurrentStore;

/**
 * Shared helpers for admin Livewire components: current store resolution,
 * toast notifications, and money formatting.
 */
trait ResolvesAdminStore
{
    protected function store(): Store
    {
        $current = app(CurrentStore::class);
        $store = $current->get();

        if ($store !== null) {
            return $store;
        }

        $store = $this->resolveAdminStoreFromSession();

        if ($store !== null) {
            $current->set($store);

            return $store;
        }

        abort(404);
    }

    protected function resolveAdminStoreFromSession(): ?Store
    {
        if (! app()->bound('session')) {
            return null;
        }

        $storeId = session()->get('current_store_id');

        if ($storeId === null) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            return null;
        }

        $user = request()->user();

        if ($user !== null && ! $user->stores()->where('stores.id', $store->getKey())->exists()) {
            abort(403);
        }

        return $store;
    }

    protected function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    protected function flashToast(string $type, string $message): void
    {
        session()->flash('toast', ['type' => $type, 'message' => $message]);
    }

    public function money(int $cents, ?string $currency = null): string
    {
        $currency = $currency ?? $this->store()->default_currency;

        $symbol = match (strtoupper($currency)) {
            'EUR' => '€',
            'GBP' => '£',
            default => '$',
        };

        return $symbol.number_format($cents / 100, 2);
    }
}
