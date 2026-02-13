<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentication required.');
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
            fn (string $role) => StoreUserRole::from($role),
            $roles
        );

        if (! in_array($userRole, $allowedRoles)) {
            abort(403, 'You do not have the required role.');
        }

        return $next($request);
    }
}
