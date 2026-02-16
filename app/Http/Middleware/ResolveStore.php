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
        $store = $this->resolveFromHostname($request)
            ?? $this->resolveFromSession($request);

        if (! $store) {
            abort(404, 'Store not found.');
        }

        if ($this->isStorefrontRequest($request) && $store->status === StoreStatus::Suspended) {
            abort(503, 'Store is temporarily unavailable.');
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    protected function resolveFromHostname(Request $request): ?Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            300,
            fn () => StoreDomain::query()
                ->where('domain', $hostname)
                ->value('store_id')
        );

        if (! $storeId) {
            return null;
        }

        return Store::find($storeId);
    }

    protected function resolveFromSession(Request $request): ?Store
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return null;
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId) {
            $store = Store::find($storeId);

            if ($store && $user->stores()->where('stores.id', $store->id)->exists()) {
                return $store;
            }
        }

        $firstStore = $user->stores()->first();

        if ($firstStore) {
            $request->session()->put('current_store_id', $firstStore->id);
        }

        return $firstStore;
    }

    protected function isStorefrontRequest(Request $request): bool
    {
        return ! $request->is('admin/*') && ! $request->is('admin');
    }
}
