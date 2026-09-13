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
        $store = app(CurrentStore::class)->get();

        abort_if($store === null, 404);

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
