<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce a Sanctum token ability (scope) on an Admin REST API route
 * (spec 06 §1.3). Missing abilities yield 403.
 */
class CheckTokenAbility
{
    /**
     * @param  string  $ability  The required token ability, e.g. "read-products"
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->tokenCan($ability), 403, "This token is missing the required ability: {$ability}.");

        return $next($request);
    }
}
