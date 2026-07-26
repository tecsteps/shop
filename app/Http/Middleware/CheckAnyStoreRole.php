<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameter-less "any role" variant of CheckStoreRole.
 *
 * Verifies the authenticated user holds any membership role for the
 * current store. Fine-grained restrictions stay in policies/gates.
 * Exists so the middleware can be registered as Livewire persistent
 * middleware (middleware parameters are not supported there).
 */
class CheckAnyStoreRole extends CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        return parent::handle($request, $next, 'owner', 'admin', 'staff', 'support');
    }
}
