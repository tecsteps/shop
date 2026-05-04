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
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->is('admin*')
            ? $this->resolveAdminStore($request)
            : $this->resolveStorefrontStore($request);

        abort_unless($store, 404);

        app()->instance('current_store', $store);

        if (! $request->is('admin*') && $store->status === StoreStatus::Suspended) {
            abort(503);
        }

        if ($request->is('admin*') && $store->status === StoreStatus::Suspended && ! $request->isMethodSafe()) {
            abort(403);
        }

        return $next($request);
    }

    private function resolveStorefrontStore(Request $request): ?Store
    {
        $hostname = $request->getHost();
        $cacheKey = "store_domain:{$hostname}";

        $storeId = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->value('store_id');
        });

        return $storeId ? Store::query()->find($storeId) : null;
    }

    private function resolveAdminStore(Request $request): ?Store
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            $storeId = $user->stores()->oldest('stores.id')->value('stores.id');

            if ($storeId) {
                $request->session()->put('current_store_id', $storeId);
            }
        }

        if (! $storeId || ! $user->stores()->whereKey($storeId)->exists()) {
            return null;
        }

        return Store::query()->find($storeId);
    }
}
