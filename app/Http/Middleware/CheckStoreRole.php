<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\StoreUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * Verify the authenticated user holds one of the given roles for the
     * current store. Usage: role.check:owner,admin
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(app()->bound('current_store'), 403, 'You do not have access to this store.');

        $store = app('current_store');
        $user = $request->user();

        $storeUser = $user === null ? null : StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        abort_if($storeUser === null, 403, 'You do not have access to this store.');

        $allowedRoles = array_map(
            fn (string $role): StoreUserRole => StoreUserRole::from($role),
            $roles,
        );

        abort_unless(in_array($storeUser->role, $allowedRoles, true), 403, 'Insufficient permissions.');

        $request->attributes->set('store_user', $storeUser);

        return $next($request);
    }
}
