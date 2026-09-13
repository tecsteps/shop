<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Tenancy\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current store and binds it to the container.
 *
 * Storefront context: resolves from the request hostname via store_domains
 * (cached 5 minutes). Unknown hostname aborts 404, suspended store aborts 503.
 *
 * Admin web context: resolves from the session `current_store_id` key and
 * verifies membership. Suspended stores allow reads but abort 403 on mutations.
 *
 * Admin API context: resolves from the {storeId} route parameter (404 when
 * missing). Membership is enforced at the endpoint/policy layer so platform
 * endpoints (invites) keep working for non-member platform admins.
 */
class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAdminContext($request)) {
            return $this->resolveForAdmin($request, $next);
        }

        return $this->resolveForStorefront($request, $next);
    }

    protected function isAdminContext(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*') || $request->is('api/admin/*');
    }

    protected function resolveForStorefront(Request $request, Closure $next): Response
    {
        $store = $this->findByHostname($request->getHost());

        if ($store === null && is_string($request->query('store'))) {
            $store = Store::query()->where('handle', $request->query('store'))->first();
        }

        // Unknown hostname (e.g. the bare Herd project host shop.test with no
        // store_domains row) intentionally 404s per spec 05 section 1.1 step 5.
        // No default-store fallback: every storefront hostname must be registered.
        if ($store === null) {
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503, 'This store is currently unavailable.');
        }

        $this->bind($store);

        if ($request->hasSession()) {
            $request->session()->put('storefront_store_id', $store->getKey());
        }

        return $next($request);
    }

    protected function resolveForAdmin(Request $request, Closure $next): Response
    {
        $routeStoreId = $request->route('storeId') ?? $request->route('store');

        if ($routeStoreId !== null) {
            $store = Store::query()->find($routeStoreId);

            if ($store === null) {
                abort(404);
            }

            $this->guardSuspendedMutation($request, $store);
            $this->bind($store);

            return $next($request);
        }

        if ($request->is('api/admin/*')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            $storeId = $this->autoSelectSingleStore($request);

            if ($storeId === null) {
                return redirect()->route('admin.store-selector');
            }
        }

        $isMember = $user->stores()->where('stores.id', $storeId)->exists();

        if (! $isMember) {
            abort(403);
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            $request->session()->forget('current_store_id');

            return redirect()->route('admin.store-selector');
        }

        $this->guardSuspendedMutation($request, $store);
        $this->bind($store);

        return $next($request);
    }

    protected function findByHostname(string $hostname): ?Store
    {
        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            now()->addMinutes(5),
            fn (): ?int => StoreDomain::query()->where('hostname', $hostname)->value('store_id')
        );

        if ($storeId === null) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function autoSelectSingleStore(Request $request): ?int
    {
        $storeIds = $request->user()->stores()->pluck('stores.id');

        if ($storeIds->count() !== 1) {
            return null;
        }

        $request->session()->put('current_store_id', $storeIds->first());

        return (int) $storeIds->first();
    }

    protected function guardSuspendedMutation(Request $request, Store $store): void
    {
        if ($store->status === StoreStatus::Suspended && ! $request->isMethodCacheable()) {
            abort(403, 'This store is suspended and cannot be modified.');
        }
    }

    protected function bind(Store $store): void
    {
        app(CurrentStore::class)->set($store);
        view()->share('currentStore', $store);
    }
}
