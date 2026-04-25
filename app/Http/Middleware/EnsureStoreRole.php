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
        if (! app()->bound('current_store')) {
            abort(403);
        }

        $role = $request->user()?->roleForStore(app('current_store'));

        if (! $role) {
            abort(403);
        }

        if ($roles !== [] && ! in_array($role->value, $roles, true)) {
            abort(403);
        }

        if ($role === StoreUserRole::Support && $request->isMethodSafe() === false) {
            abort(403);
        }

        return $next($request);
    }
}

