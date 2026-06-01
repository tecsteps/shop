<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the request is authenticated via the `customer` guard.
 *
 * Unauthenticated customers have their intended URL stored in the session and
 * are redirected to the storefront login route.
 */
class CustomerAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('account.login');
    }
}
