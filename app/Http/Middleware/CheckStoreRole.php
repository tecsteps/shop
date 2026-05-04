<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $user = $request->user();

        abort_unless($store instanceof Store && $user, 403);

        $role = $user->roleForStore($store);

        if ($roles === []) {
            abort_unless($role !== null, 403);

            return $next($request);
        }

        $allowedRoles = array_filter(array_map(
            fn (string $role): ?StoreUserRole => StoreUserRole::tryFrom($role),
            $roles,
        ));

        abort_unless($role !== null && in_array($role, $allowedRoles, true), 403);

        return $next($request);
    }
}
