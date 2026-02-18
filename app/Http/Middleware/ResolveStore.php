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
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        if ($context === 'admin') {
            return $this->resolveForAdmin($request, $next);
        }

        return $this->resolveForStorefront($request, $next);
    }

    protected function resolveForStorefront(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();
        $cacheKey = "store_domain:{$hostname}";

        $storeId = Cache::remember($cacheKey, 300, function () use ($hostname) {
            return StoreDomain::where('hostname', $hostname)->value('store_id');
        });

        if (! $storeId) {
            abort(404);
        }

        $store = Store::find($storeId);

        if (! $store) {
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    protected function resolveForAdmin(Request $request, Closure $next): Response
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            abort(403);
        }

        $store = Store::find($storeId);

        if (! $store) {
            abort(403);
        }

        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $hasAccess = $user->stores()->where('stores.id', $store->id)->exists();

        if (! $hasAccess) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }
}
