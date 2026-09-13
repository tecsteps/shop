<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\ThemeService;
use App\Tenancy\CurrentStore;
use Illuminate\Support\Facades\Cache;

trait ResolvesStore
{
    protected function store(): Store
    {
        $current = app(CurrentStore::class);
        $store = $current->get();

        if ($store !== null) {
            return $store;
        }

        $store = $this->resolveStoreFromRequest();

        if ($store !== null) {
            $current->set($store);

            return $store;
        }

        abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    protected function themeSettings(): array
    {
        return app(ThemeService::class)->settings($this->store());
    }

    protected function resolveStoreFromRequest(): ?Store
    {
        $request = request();

        $hostname = $request->getHost();

        if ($hostname !== '') {
            $storeId = Cache::remember(
                "store_domain:{$hostname}",
                now()->addMinutes(5),
                fn (): ?int => StoreDomain::query()->where('hostname', $hostname)->value('store_id')
            );

            if ($storeId !== null) {
                $store = Store::query()->find($storeId);

                if ($store !== null) {
                    if ($store->status === StoreStatus::Suspended) {
                        abort(503, 'This store is currently unavailable.');
                    }

                    return $store;
                }
            }
        }

        if (app()->bound('session')) {
            $sessionStoreId = session()->get('storefront_store_id');

            if ($sessionStoreId !== null) {
                $store = Store::query()->find($sessionStoreId);

                if ($store !== null && $store->status !== StoreStatus::Suspended) {
                    return $store;
                }
            }
        }

        return null;
    }
}
