<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces that the authenticated Sanctum token carries a required ability
 * (scope). Returns 403 with the standard authorization message when it does not.
 *
 * Registered as the `ability` route middleware alias and applied per-route, e.g.
 * `->middleware('ability:write-products')`.
 */
class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->tokenCan($ability)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
