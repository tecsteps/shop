<?php

namespace App\Http\Middleware;

use App\Enums\AppInstallationStatus;
use App\Models\OauthToken;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $store = $this->storeFromRoute($request);

        app()->forgetInstance('admin_api_oauth_token');
        app()->instance('current_store', $store);

        if ($request->user() !== null) {
            if ($request->user()->stores()->whereKey($store->getKey())->exists()) {
                return $next($request);
            }

            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = OauthToken::query()
            ->with('installation')
            ->where('access_token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $token instanceof OauthToken || $token->isExpired()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $installation = $token->installation;

        if ($installation === null
            || $installation->status !== AppInstallationStatus::Active
            || (int) $installation->store_id !== $store->getKey()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->hasAbilities($token, $abilities)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('admin_api_oauth_token', $token);
        app()->instance('admin_api_oauth_token', $token);

        return $next($request);
    }

    private function storeFromRoute(Request $request): Store
    {
        $store = $request->route('store');

        if ($store instanceof Store) {
            return $store;
        }

        return Store::query()->findOrFail($store);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function hasAbilities(OauthToken $token, array $abilities): bool
    {
        if ($abilities === []) {
            return true;
        }

        $tokenAbilities = $token->abilities_json ?? [];

        if (in_array('*', $tokenAbilities, true)) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (! in_array($ability, $tokenAbilities, true)) {
                return false;
            }
        }

        return true;
    }
}
