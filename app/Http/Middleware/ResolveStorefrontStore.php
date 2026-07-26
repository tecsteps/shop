<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameter-less storefront variant of ResolveStore.
 *
 * Exists so the middleware can be registered as Livewire persistent
 * middleware (middleware parameters are not supported there).
 */
class ResolveStorefrontStore extends ResolveStore
{
    public function handle(Request $request, Closure $next, string $source = 'storefront'): Response
    {
        return parent::handle($request, $next, 'storefront');
    }
}
