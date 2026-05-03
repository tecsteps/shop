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
        $store = $this->isAdminRequest($request)
            ? $this->resolveAdminStore($request)
            : $this->resolveStorefrontStore($request);

        app()->instance('current_store', $store);

        return $next($request);
    }

    private function resolveStorefrontStore(Request $request): Store
    {
        $hostname = $request->getHost();
        $cacheKey = 'store_domain:'.$hostname;

        $storeId = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->value('store_id');
        });

        if (! $storeId) {
            abort(404);
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            Cache::forget($cacheKey);
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503);
        }

        return $store;
    }

    private function resolveAdminStore(Request $request): Store
    {
        $user = $request->user();
        $storeId = $request->session()->get('current_store_id');

        if (! $user || ! $storeId) {
            abort(403);
        }

        $store = Store::query()
            ->whereKey($storeId)
            ->whereHas('users', fn ($query) => $query->whereKey($user->getKey()))
            ->first();

        if (! $store) {
            abort(403);
        }

        return $store;
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->routeIs('admin.*') || $request->is('admin') || $request->is('admin/*');
    }
}
