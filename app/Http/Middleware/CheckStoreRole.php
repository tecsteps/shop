<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $user = $request->user();

        if (! $store || ! $user) {
            abort(403, 'You do not have access to this store.');
        }

        $role = $user->roleForStore($store);

        if ($role === null) {
            abort(403, 'You do not have access to this store.');
        }

        if ($roles !== [] && ! in_array($role->value, $roles, true)) {
            abort(403, 'Insufficient permissions.');
        }

        $request->attributes->set('store_user', $role);

        return $next($request);
    }
}
