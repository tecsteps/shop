<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Store;

trait EnsuresStore
{
    protected function ensureCurrentStore(): Store
    {
        if (! app()->bound('current_store')) {
            /** @var Store $store */
            $store = Store::first() ?? Store::factory()->create();
            app()->instance('current_store', $store);
        }

        /** @var Store $current */
        $current = app('current_store');

        return $current;
    }
}
