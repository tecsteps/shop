<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $store = app('current_store');

        if (! $user || ! $store instanceof Store) {
            abort(403);
        }

        $userRole = $user->roleForStore($store);

        if (! $userRole) {
            abort(403);
        }

        if (! empty($roles)) {
            $allowedRoles = array_map(
                fn (string $role) => StoreUserRole::from($role),
                $roles
            );

            if (! in_array($userRole, $allowedRoles)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
