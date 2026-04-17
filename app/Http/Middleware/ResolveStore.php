<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminRoute = str_starts_with($request->path(), 'admin');

        if ($isAdminRoute) {
            return $this->resolveForAdmin($request, $next);
        }

        return $this->resolveForStorefront($request, $next);
    }

    /**
     * Resolve store for admin routes using session.
     */
    protected function resolveForAdmin(Request $request, Closure $next): Response
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            return $this->resolveAdminStoreFromUser($request, $next);
        }

        $store = Store::find($storeId);

        if (! $store) {
            $request->session()->forget('current_store_id');

            return $this->resolveAdminStoreFromUser($request, $next);
        }

        if ($request->user() && ! $request->user()->stores()->where('stores.id', $store->id)->exists()) {
            abort(403, 'You do not have access to this store.');
        }

        $this->bindStore($store, $request);

        return $next($request);
    }

    /**
     * Resolve the admin store from the authenticated user's first store.
     */
    protected function resolveAdminStoreFromUser(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $store = $user->stores()->first();

        if (! $store) {
            abort(403, 'You are not associated with any store.');
        }

        $request->session()->put('current_store_id', $store->id);
        $this->bindStore($store, $request);

        return $next($request);
    }

    /**
     * Resolve store for storefront routes using hostname.
     */
    protected function resolveForStorefront(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();

        $cacheKey = 'store_domain:'.$hostname;

        $storeId = Cache::remember($cacheKey, 300, function () use ($hostname) {
            $domain = StoreDomain::where('hostname', $hostname)->first();

            return $domain?->store_id;
        });

        if (! $storeId) {
            abort(404, 'Store not found for this domain.');
        }

        $store = Store::find($storeId);

        if (! $store) {
            Cache::forget($cacheKey);
            abort(404, 'Store not found.');
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503, 'This store is currently unavailable.');
        }

        $this->bindStore($store, $request);

        return $next($request);
    }

    /**
     * Bind the store as a singleton and store in session.
     */
    protected function bindStore(Store $store, Request $request): void
    {
        app()->instance('current_store', $store);
        $request->session()->put('current_store_id', $store->id);
    }
}
