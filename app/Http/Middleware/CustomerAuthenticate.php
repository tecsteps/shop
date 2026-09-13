<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the request is authenticated via the `customer` guard.
 * Unauthenticated customers are redirected to the storefront login page,
 * with the current URL stored for a post-login redirect.
 */
class CustomerAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401);
        }

        return redirect()->guest(route('customer.login'));
    }
}
