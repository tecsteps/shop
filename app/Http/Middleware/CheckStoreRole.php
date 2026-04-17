<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if (! app()->bound('current_store')) {
            abort(403, 'No store context.');
        }

        /** @var Store $store */
        $store = app('current_store');

        $userRole = $user->roleForStore($store);

        if (! $userRole) {
            abort(403, 'You do not have access to this store.');
        }

        $allowedRoles = array_map(
            fn (string $role): ?StoreUserRole => StoreUserRole::tryFrom(trim($role)),
            explode(',', $roles),
        );

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}
