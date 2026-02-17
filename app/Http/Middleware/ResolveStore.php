<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
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
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $store = $this->isAdminRoute($request)
            ? $this->resolveFromSession($request)
            : $this->resolveFromHostname($request);

        if ($store->status === StoreStatus::Suspended) {
            if ($this->isAdminRoute($request)) {
                abort(403, 'This store is currently suspended.');
            }

            abort(503, 'This store is currently unavailable.');
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        return $next($request);
    }

    /**
     * Resolve store from request hostname via store_domains table.
     */
    protected function resolveFromHostname(Request $request): Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store-domain:{$hostname}",
            300,
            function () use ($hostname) {
                return StoreDomain::where('hostname', $hostname)->value('store_id');
            }
        );

        if (! $storeId) {
            abort(404, 'Store not found.');
        }

        return Store::findOrFail($storeId);
    }

    /**
     * Resolve store from the admin session.
     */
    protected function resolveFromSession(Request $request): Store
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            abort(403, 'No store selected.');
        }

        $user = $request->user();

        if (! $user) {
            abort(403, 'You do not have access to this store.');
        }

        $hasAccess = $user->stores()->where('stores.id', $storeId)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this store.');
        }

        return Store::findOrFail($storeId);
    }

    /**
     * Check if the current request is for an admin route.
     */
    protected function isAdminRoute(Request $request): bool
    {
        return str_starts_with($request->path(), 'admin');
    }
}
