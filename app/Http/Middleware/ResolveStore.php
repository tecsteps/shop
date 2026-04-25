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
    public function handle(Request $request, Closure $next): Response
    {
        $store = str_starts_with($request->path(), 'admin')
            ? $this->resolveForAdmin($request)
            : $this->resolveForStorefront($request);

        if (! $store) {
            abort(str_starts_with($request->path(), 'admin') ? 403 : 404);
        }

        if (! str_starts_with($request->path(), 'admin') && $store->status === StoreStatus::Suspended) {
            abort(503);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    private function resolveForStorefront(Request $request): ?Store
    {
        $host = strtolower($request->getHost());

        if ($host === 'shop.test') {
            $host = 'acme-fashion.test';
        }

        $storeId = Cache::remember("store-domain:{$host}", now()->addMinutes(5), function () use ($host): ?int {
            return StoreDomain::query()
                ->where('hostname', $host)
                ->where('type', 'storefront')
                ->value('store_id');
        });

        return $storeId ? Store::query()->find($storeId) : null;
    }

    private function resolveForAdmin(Request $request): ?Store
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $storeId = $request->session()->get('current_store_id')
            ?? $user->stores()->orderBy('stores.id')->value('stores.id');

        if (! $storeId) {
            return null;
        }

        $hasAccess = $user->stores()->whereKey($storeId)->exists();

        if (! $hasAccess) {
            return null;
        }

        $request->session()->put('current_store_id', $storeId);

        return Store::query()->find($storeId);
    }
}

