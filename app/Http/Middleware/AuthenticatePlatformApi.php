<?php

namespace App\Http\Middleware;

use App\Enums\AppInstallationStatus;
use App\Enums\StoreUserRole;
use App\Models\OauthToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePlatformApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance('admin_api_oauth_token');

        $user = $request->user();

        if ($user instanceof User) {
            if ($this->userCanManagePlatform($user)) {
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

        if ($installation === null || $installation->status !== AppInstallationStatus::Active) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->hasManagePlatformAbility($token)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('admin_api_oauth_token', $token);
        app()->instance('admin_api_oauth_token', $token);

        return $next($request);
    }

    private function userCanManagePlatform(User $user): bool
    {
        return $user->stores()
            ->wherePivot('role', StoreUserRole::Owner->value)
            ->exists();
    }

    private function hasManagePlatformAbility(OauthToken $token): bool
    {
        $abilities = $token->abilities_json ?? [];

        return in_array('*', $abilities, true) || in_array('manage-platform', $abilities, true);
    }
}
