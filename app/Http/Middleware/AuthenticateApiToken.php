<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            abort(401, 'Missing API token.');
        }

        $token = app(ApiTokenService::class)->findValidToken($plainTextToken, $ability);

        if (! $token) {
            abort(403, 'Invalid API token or ability.');
        }

        $routeStore = $request->route('store');
        $store = $routeStore instanceof Store ? $routeStore : Store::query()->find($routeStore);

        if (! $store instanceof Store || $store->id !== $token->store_id) {
            abort(403, 'API token cannot access this store.');
        }

        app()->instance('current_store', $store);
        app()->instance('current_api_token', $token);

        return $next($request);
    }
}
