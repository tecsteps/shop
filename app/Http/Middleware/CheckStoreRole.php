<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated admin user holds one of the required roles for the
 * current store. Applied as `role.check:owner,admin` (comma-separated roles).
 *
 * Resolves the current store from the container, looks up the user's
 * store_users membership, and aborts 403 when no membership exists or the role
 * is not in the allowed list. The resolved StoreUser pivot is attached to the
 * request attributes under `store_user` for downstream use.
 */
class CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! app()->bound('current_store')) {
            abort(403, 'No store resolved.');
        }

        /** @var Store $store */
        $store = app('current_store');

        $user = Auth::guard('web')->user();

        if ($user === null) {
            abort(403, 'You do not have access to this store.');
        }

        $membership = $store->users()
            ->withoutGlobalScopes()
            ->wherePivot('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You do not have access to this store.');
        }

        $allowed = array_map(
            fn (string $role): StoreUserRole => StoreUserRole::from($role),
            $roles,
        );

        if ($allowed !== [] && ! in_array($membership->membership->role, $allowed, true)) {
            abort(403, 'Insufficient permissions.');
        }

        $request->attributes->set('store_user', $membership->membership);

        return $next($request);
    }
}
