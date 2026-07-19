<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Resolve the current store and bind it to the container.
     *
     * Storefront requests resolve from the request hostname; admin
     * requests resolve from the session's current_store_id and verify
     * the authenticated user holds a store membership.
     */
    public function handle(Request $request, Closure $next, string $source = 'storefront'): Response
    {
        $store = $source === 'admin'
            ? $this->resolveAdminStore($request)
            : $this->resolveStorefrontStore($request);

        app()->instance('current_store', $store);
        view()->share('currentStore', $store);

        return $next($request);
    }

    /**
     * Resolve the store from the request hostname (storefront).
     */
    protected function resolveStorefrontStore(Request $request): Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            now()->addMinutes(5),
            function () use ($hostname): ?int {
                $id = StoreDomain::query()->where('hostname', $hostname)->value('store_id');

                return $id === null ? null : (int) $id;
            },
        );

        abort_if($storeId === null, 404, 'Store not found.');

        $store = Store::query()->find($storeId);

        abort_if($store === null, 404, 'Store not found.');
        abort_if($store->status === StoreStatus::Suspended, 503, 'This store is currently unavailable.');

        return $store;
    }

    /**
     * Resolve the store from the session (admin panel).
     */
    protected function resolveAdminStore(Request $request): Store
    {
        $storeId = $request->session()->get('current_store_id');
        $user = $request->user();

        abort_if($storeId === null || $user === null, 403, 'You do not have access to this store.');

        $hasMembership = StoreUser::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->getKey())
            ->exists();

        abort_unless($hasMembership, 403, 'You do not have access to this store.');

        $store = Store::query()->find($storeId);

        abort_if($store === null, 403, 'You do not have access to this store.');
        abort_if($store->status === StoreStatus::Suspended, 403, 'This store is currently unavailable.');

        return $store;
    }
}
