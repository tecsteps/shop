<?php

namespace App\Http\Middleware;

use App\Enums\StoreDomainType;
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
 * Two modes:
 *   - storefront: resolve the store from the request hostname via the
 *     store_domains table, caching the hostname -> store_id map for 5 minutes.
 *     Returns 404 for an unknown hostname and 503 for a suspended store.
 *   - admin: resolve the store from the session key `current_store_id` and
 *     verify the authenticated user has a store_users membership; 403 otherwise.
 *
 * On the initial page request the mode is passed explicitly by the `storefront`
 * / `admin` route-group middleware (`ResolveStore:storefront` / `:admin`).
 *
 * This middleware is ALSO registered as Livewire persistent middleware
 * ({@see \App\Providers\AppServiceProvider}) so it re-runs on the shared
 * `/livewire/update` endpoint — otherwise nested/global components (e.g. the
 * layout CartDrawer) that read `app('current_store')` would 500 on update
 * requests, since that endpoint does not re-apply the route-group middleware.
 * Persistent middleware drops arguments, so when invoked without `$mode` the
 * middleware self-detects the surface from the request (see {@see detectMode()}).
 *
 * After resolution the Store is bound as the container instance `current_store`
 * and shared with all views as `currentStore`. Binding is idempotent: if a
 * store is already bound (e.g. the route-group middleware ran first), this is a
 * no-op.
 */
class ResolveStore
{
    /**
     * Cache TTL (seconds) for hostname -> store_id resolution.
     */
    private const CACHE_TTL = 300;

    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        // Idempotent: the route-group middleware may have already resolved the
        // store on the initial request. Never re-resolve or double-abort.
        if (app()->bound('current_store')) {
            return $next($request);
        }

        $mode ??= $this->detectMode($request);

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
     * Self-detect the resolution mode when invoked without an explicit `$mode`
     * (i.e. as Livewire persistent middleware on the `/livewire/update` route).
     *
     * The storefront and admin surfaces share the same host (e.g. shop.test)
     * and differ by URL path: admin lives under `/admin`; the admin store is
     * session-based, not host-based. Detection, in priority order:
     *
     *   1. Originating path under `/admin` (read from the Referer on a Livewire
     *      update, else the request path) => admin. Strongest positive signal.
     *   2. Request host matches a storefront `store_domains` row => storefront.
     *      The host cannot be stripped like a Referer, and a host that maps to a
     *      store is by definition the storefront surface, so this takes
     *      precedence over the session heuristic — a stale web/admin session
     *      while browsing the storefront must not hijack a storefront update.
     *   3. Otherwise (host is not a storefront domain): fall back to the
     *      session + web-guard state — an authenticated web user with a selected
     *      store is treated as admin.
     */
    private function detectMode(Request $request): string
    {
        $contextPath = $this->contextPath($request);

        if ($contextPath !== null && $this->pathIsAdmin($contextPath)) {
            return 'admin';
        }

        // A host that resolves to a storefront-type domain is unambiguously the
        // storefront surface (admin shares the host but is session-based). Only
        // storefront-type domains count here so admin/api hosts don't get
        // mis-detected.
        if ($this->hostIsStorefront($request->getHost())) {
            return 'storefront';
        }

        // No path or host signal: fall back to the session + web-guard state.
        if ($request->hasSession()
            && $request->session()->get('current_store_id') !== null
            && Auth::guard('web')->check()
        ) {
            return 'admin';
        }

        return 'storefront';
    }

    /**
     * The path of the page the request originated from: the Referer path when
     * present (Livewire update requests), otherwise the request's own path.
     */
    private function contextPath(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if ($referer !== null && $referer !== '') {
            return parse_url($referer, PHP_URL_PATH) ?: '/';
        }

        $path = $request->path();

        // The shared Livewire update endpoint tells us nothing about the
        // surface; without a Referer there is no path signal.
        if (str_starts_with($path, 'livewire')) {
            return null;
        }

        return '/'.ltrim($path, '/');
    }

    /**
     * Whether a URL path belongs to the admin surface.
     */
    private function pathIsAdmin(string $path): bool
    {
        $segment = trim($path, '/');

        return $segment === 'admin' || str_starts_with($segment, 'admin/');
    }

    /**
     * Resolve the store from the request hostname (storefront routes).
     */
    private function resolveFromHostname(Request $request): Store
    {
        $hostname = $request->getHost();
        $storeId = $this->storefrontStoreId($hostname);

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
     * The store id a hostname maps to via store_domains, or null when unknown.
     * Cached for {@see self::CACHE_TTL} seconds under `store_domain:{hostname}`.
     */
    private function storefrontStoreId(string $hostname): ?int
    {
        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            self::CACHE_TTL,
            fn () => StoreDomain::query()->where('hostname', $hostname)->value('store_id'),
        );

        return $storeId === null ? null : (int) $storeId;
    }

    /**
     * Whether a hostname maps to a storefront-type domain. Used only by mode
     * self-detection so admin/api hosts are not treated as the storefront
     * surface. Cached under `store_domain:storefront:{hostname}`.
     */
    private function hostIsStorefront(string $hostname): bool
    {
        return (bool) Cache::remember(
            "store_domain:storefront:{$hostname}",
            self::CACHE_TTL,
            fn () => StoreDomain::query()
                ->where('hostname', $hostname)
                ->where('type', StoreDomainType::Storefront->value)
                ->exists(),
        );
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
