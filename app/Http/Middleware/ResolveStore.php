<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current store and binds it to the container as `current_store`.
 *
 * Two modes, selected by the `$mode` route parameter:
 *   - storefront (default): resolve the store from the request hostname via the
 *     store_domains table, caching the hostname -> store_id map for 5 minutes.
 *     Returns 404 for an unknown hostname and 503 for a suspended store.
 *   - admin: resolve the store from the session key `current_store_id` and
 *     verify the authenticated user has a store_users membership; 403 otherwise.
 *
 * After resolution the Store is bound as the container instance `current_store`
 * and shared with all views as `currentStore`.
 */
class ResolveStore
{
    /**
     * Cache TTL (seconds) for hostname -> store_id resolution.
     */
    private const CACHE_TTL = 300;

    public function handle(Request $request, Closure $next, string $mode = 'storefront'): Response
    {
        $store = $mode === 'admin'
            ? $this->resolveFromSession($request)
            : $this->resolveFromHostname($request);

        if ($mode !== 'admin' && $store->isSuspended()) {
            abort(503, 'This store is currently unavailable.');
        }

        $this->bind($store);

        return $next($request);
    }

    /**
     * Resolve the store from the request hostname (storefront routes).
     */
    private function resolveFromHostname(Request $request): Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            self::CACHE_TTL,
            fn () => StoreDomain::query()->where('hostname', $hostname)->value('store_id'),
        );

        if ($storeId === null) {
            abort(404, 'Store not found.');
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            // Stale cache entry pointing at a deleted store; forget and 404.
            Cache::forget("store_domain:{$hostname}");
            abort(404, 'Store not found.');
        }

        return $store;
    }

    /**
     * Resolve the store from the session (admin routes).
     */
    private function resolveFromSession(Request $request): Store
    {
        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            abort(403, 'No store selected.');
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            abort(403, 'No store selected.');
        }

        $user = Auth::guard('web')->user();

        if ($user === null || $user->roleForStore($store) === null) {
            abort(403, 'You do not have access to this store.');
        }

        return $store;
    }

    /**
     * Bind the resolved store to the container and share it with views.
     */
    private function bind(Store $store): void
    {
        app()->instance('current_store', $store);
        View::share('currentStore', $store);
    }
}
