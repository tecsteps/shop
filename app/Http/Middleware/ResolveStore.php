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
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $store = $context === 'admin'
            ? $this->resolveForAdmin($request)
            : $this->resolveForStorefront($request);

        if (! $store) {
            abort(404, 'Store not found.');
        }

        if ($store->status->value === 'suspended') {
            abort(503, 'Store is currently unavailable.');
        }

        app()->singleton('current_store', fn (): Store => $store);

        return $next($request);
    }

    private function resolveForStorefront(Request $request): ?Store
    {
        $hostname = $request->getHost();

        $cacheKey = 'store_domain:'.$hostname;

        $storeId = Cache::remember($cacheKey, 300, function () use ($hostname): ?int {
            $domain = StoreDomain::query()
                ->where('hostname', $hostname)
                ->first();

            return $domain?->store_id;
        });

        if (! $storeId) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    private function resolveForAdmin(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            return null;
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        $hasAccess = $user->stores()
            ->where('stores.id', $store->id)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        return $store;
    }
}
