<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Store;
use App\Services\ThemeService;
use App\Tenancy\CurrentStore;

trait ResolvesStore
{
    protected function store(): Store
    {
        $store = app(CurrentStore::class)->get();

        abort_if($store === null, 404);

        return $store;
    }

    /**
     * @return array<string, mixed>
     */
    protected function themeSettings(): array
    {
        return app(ThemeService::class)->settings($this->store());
    }
}
