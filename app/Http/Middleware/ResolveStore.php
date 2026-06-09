<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Resolve the current store from the hostname (storefront) or session (admin)
     * and bind it to the service container as "current_store".
     */
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $store = $context === 'admin'
            ? $this->resolveFromSession($request)
            : $this->resolveFromHostname($request);

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        return $next($request);
    }

    /**
     * Resolve the store from the request hostname via the store_domains table.
     */
    protected function resolveFromHostname(Request $request): Store
    {
        $hostname = strtolower($request->getHost());

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            now()->addMinutes(5),
            fn (): ?int => StoreDomain::query()->where('hostname', $hostname)->value('store_id'),
        );

        if ($storeId === null) {
            abort(404, 'Store not found.');
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            abort(404, 'Store not found.');
        }

        if ($store->isSuspended()) {
            abort(503, 'This store is currently unavailable.');
        }

        return $store;
    }

    /**
     * Resolve the store from the session for admin requests and verify membership.
     */
    protected function resolveFromSession(Request $request): Store
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'You do not have access to this store.');
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            $storeId = $user->stores()->value('stores.id');

            if ($storeId === null) {
                abort(403, 'You do not have access to this store.');
            }

            $request->session()->put('current_store_id', $storeId);
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            abort(404, 'Store not found.');
        }

        if (! $user->stores()->whereKey($store->getKey())->exists()) {
            abort(403, 'You do not have access to this store.');
        }

        if ($store->isSuspended() && ! $request->isMethodSafe()) {
            abort(403, 'This store is currently suspended.');
        }

        return $store;
    }
}
