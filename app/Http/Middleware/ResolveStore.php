<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->is('api/admin/*')
            ? $this->resolveAdminApiStore($request)
            : ($request->is('admin/*') ? $this->resolveAdminStore($request) : $this->resolveStorefrontStore($request));

        if (! $store instanceof Store) {
            abort(404);
        }

        if ($request->is('api/admin/*') && ! $request->user()->stores()->whereKey($store->id)->exists()) {
            abort(403);
        }

        if ($store->status === 'suspended') {
            if (! $request->is('admin/*') && ! $request->is('api/admin/*')) {
                abort(503, 'This storefront is temporarily unavailable.');
            }

            if (! $request->isMethodSafe()) {
                abort(403, 'This store is suspended and cannot be changed.');
            }
        }

        app()->instance('current_store', $store);
        view()->share('currentStore', $store);

        return $next($request);
    }

    private function resolveStorefrontStore(Request $request): ?Store
    {
        $hostname = strtolower($request->getHost());
        $storeId = Cache::remember("store-domain:{$hostname}", now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->where('type', 'storefront')
                ->value('store_id');
        });

        return $storeId ? Store::query()->find($storeId) : null;
    }

    private function resolveAdminStore(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId && $request->user()) {
            $storeId = $request->user()->stores()->value('stores.id');
        }

        if (! $storeId || ! $request->user()) {
            return null;
        }

        if (! $request->user()->stores()->whereKey($storeId)->exists()) {
            abort(403);
        }

        $request->session()->put('current_store_id', $storeId);

        return Store::query()->find($storeId);
    }

    private function resolveAdminApiStore(Request $request): ?Store
    {
        $storeId = $request->route('storeId');

        return $storeId ? Store::query()->find($storeId) : null;
    }
}
