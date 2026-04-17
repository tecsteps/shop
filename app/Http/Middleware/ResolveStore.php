<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'storefront'): Response
    {
        $store = $mode === 'admin'
            ? $this->resolveForAdmin($request)
            : $this->resolveForStorefront($request);

        if ($store === null) {
            abort($mode === 'admin' ? 403 : 404, 'Store not found.');
        }

        if ($store->status === StoreStatus::Suspended) {
            if ($mode === 'admin' && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                abort(403, 'This store is currently unavailable.');
            }

            if ($mode === 'storefront') {
                abort(503, 'This store is currently unavailable.');
            }
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        return $next($request);
    }

    protected function resolveForStorefront(Request $request): ?Store
    {
        $hostname = strtolower($request->getHost());
        $cacheKey = 'store-domains:'.$hostname;

        $storeId = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->value('store_id');
        });

        if ($storeId === null) {
            Cache::forget($cacheKey);

            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function resolveForAdmin(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            return null;
        }

        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        $hasAccess = $user->stores()
            ->wherePivot('store_id', $storeId)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        return Store::query()->find($storeId);
    }
}
