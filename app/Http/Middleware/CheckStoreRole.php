<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = app('current_store');
        $user = $request->user();

        if (! $user) {
            abort(403, 'You do not have access to this store.');
        }

        $storeUser = $user->stores()
            ->where('stores.id', $store->id)
            ->first();

        if (! $storeUser) {
            abort(403, 'You do not have access to this store.');
        }

        $pivotRole = $storeUser->pivot->role;
        $userRole = $pivotRole instanceof StoreUserRole
            ? $pivotRole
            : StoreUserRole::from($pivotRole);

        if ($roles) {
            $allowedRoles = array_map(
                fn (string $role): StoreUserRole => StoreUserRole::from($role),
                $roles,
            );

            if (! in_array($userRole, $allowedRoles)) {
                abort(403, 'Insufficient permissions.');
            }
        }

        $request->attributes->set('store_user', $storeUser->pivot);

        return $next($request);
    }
}
