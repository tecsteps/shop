<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current store for the admin REST API from the `{store}` route
 * parameter and verifies the authenticated (Sanctum) user is a member.
 *
 * Binds the resolved {@see Store} as the container instance `current_store` so
 * the {@see \App\Models\Scopes\StoreScope} global scope applies to all reads.
 * Returns 404 for an unknown store and 403 when the user lacks membership.
 */
class ResolveStoreFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeId = $request->route('store');

        $store = $storeId instanceof Store
            ? $storeId
            : Store::query()->find($storeId);

        if ($store === null) {
            abort(404, 'The requested resource was not found.');
        }

        $user = $request->user();

        if ($user === null || $user->roleForStore($store) === null) {
            abort(403, 'You do not have permission to perform this action.');
        }

        app()->instance('current_store', $store);
        $request->route()->setParameter('store', $store);

        return $next($request);
    }
}
