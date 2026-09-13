<?php

namespace App\Livewire\Hooks;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Tenancy\CurrentStore;
use Illuminate\Support\Facades\Cache;
use Livewire\ComponentHook;

/**
 * Rebinds the CurrentStore on every Livewire request.
 *
 * The `store.resolve` middleware never runs on the Livewire update endpoint,
 * so without this hook CurrentStore is empty during Livewire actions and any
 * authorization reading CurrentStore::id() (create/viewAny policies, admin
 * gates) fails with 403.
 */
class ResolveStoreContext extends ComponentHook
{
    public function hydrate($memo): void
    {
        $this->ensureStoreResolved();
    }

    public function mount($params, $parent = null): void
    {
        $this->ensureStoreResolved();
    }

    protected function ensureStoreResolved(): void
    {
        $current = app(CurrentStore::class);

        if ($current->has()) {
            return;
        }

        $store = str_starts_with($this->component::class, 'App\\Livewire\\Admin')
            ? $this->resolveAdminStore()
            : $this->resolveStorefrontStore();

        if ($store !== null) {
            $current->set($store);
        }
    }

    protected function resolveAdminStore(): ?Store
    {
        $storeId = app(CurrentStore::class)->adminIdFromSession();

        if ($storeId === null) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            return null;
        }

        $user = request()->user();

        if ($user !== null && ! $user->stores()->where('stores.id', $store->getKey())->exists()) {
            return null;
        }

        return $store;
    }

    protected function resolveStorefrontStore(): ?Store
    {
        $hostname = request()->getHost();

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
