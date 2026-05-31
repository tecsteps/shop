<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Re-binds the current store for admin Livewire components.
 *
 * The `admin` middleware group ({@see \App\Http\Middleware\ResolveStore} in
 * `admin` mode) only runs on the initial full-page GET. Subsequent Livewire
 * `update` requests (wire:click, wire:model, …) hit the shared
 * `/livewire/update` endpoint, which carries only the `web` middleware, so the
 * container's `current_store` binding is lost and store-scoped queries would
 * leak across tenants.
 *
 * This concern re-resolves the store from the session's `current_store_id` on
 * every Livewire request via the framework `boot()` hook, but only when the
 * store is not already bound (so it never overrides the middleware's binding on
 * the initial page load). Resolution is session-based and membership-verified,
 * mirroring the admin branch of {@see ResolveStore}.
 */
trait BindsCurrentStore
{
    public function bootBindsCurrentStore(): void
    {
        if (app()->bound('current_store')) {
            return;
        }

        $storeId = session('current_store_id');

        if ($storeId === null) {
            return;
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            return;
        }

        $user = Auth::guard('web')->user();

        if ($user === null || $user->roleForStore($store) === null) {
            return;
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);
    }

    /**
     * The current store, or null when none is bound.
     */
    protected function currentStore(): ?Store
    {
        return app()->bound('current_store') ? app('current_store') : null;
    }
}
