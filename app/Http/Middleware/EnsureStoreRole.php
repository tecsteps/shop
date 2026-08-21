<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = app('current_store');
        $role = $request->user()?->roleForStore($store);
        $allowedRoles = array_map(fn (string $value): StoreUserRole => StoreUserRole::from($value), $roles);

        abort_unless($role !== null && in_array($role, $allowedRoles, true), 403);

        return $next($request);
    }
}
