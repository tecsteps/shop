<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuthenticate
{
    /**
     * Ensure the request is authenticated via the customer guard.
     * Guests are redirected to the storefront login page with their
     * intended URL stored in the session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect('/account/login');
        }

        return $next($request);
    }
}
