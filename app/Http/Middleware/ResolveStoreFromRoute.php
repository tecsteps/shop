<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the current store from the {storeId} route parameter for the
 * Admin REST API (spec 02 §3). API tokens carry no session, so store
 * membership is verified against store_users directly. Routes without a
 * {storeId} parameter (platform endpoints) pass through untouched.
 */
class ResolveStoreFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeId = $request->route('storeId');

        if ($storeId === null) {
            return $next($request);
        }

        $store = Store::query()->find($storeId);

        abort_if($store === null, 404, 'Store not found.');

        $user = $request->user();

        $hasMembership = $user !== null && StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        abort_unless($hasMembership, 403, 'You do not have access to this store.');

        app()->instance('current_store', $store);

        return $next($request);
    }
}
