<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\StoreUser;
use App\Tenancy\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated user holds one of the given roles for the
 * current store. Usage: `role.check:owner,admin`.
 */
class CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = app(CurrentStore::class)->get();
        $user = $request->user();

        if ($store === null || $user === null) {
            abort(403, 'You do not have access to this store.');
        }

        $storeUser = StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($storeUser === null) {
            abort(403, 'You do not have access to this store.');
        }

        $allowed = collect($roles)
            ->map(fn (string $role): ?StoreUserRole => StoreUserRole::tryFrom(strtolower(trim($role))))
            ->filter()
            ->all();

        if (! in_array($storeUser->role, $allowed, true)) {
            abort(403, 'Insufficient permissions.');
        }

        $request->attributes->set('store_user', $storeUser);

        return $next($request);
    }
}
