<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        app()->forgetInstance('current_store');

        if ($context === 'admin') {
            return $this->resolveAdminStore($request, $next);
        }

        return $this->resolveStorefrontStore($request, $next);
    }

    private function resolveStorefrontStore(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();
        $cacheKey = 'store_domain:'.$hostname;

        $storeId = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->value('store_id');
        });

        if ($storeId === null) {
            abort(404);
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    private function resolveAdminStore(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            abort(403);
        }

        $store = $user->stores()->where('stores.id', $storeId)->first();

        if ($store === null) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }
}
