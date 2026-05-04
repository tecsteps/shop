<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
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
        $token = $this->tokenFromRequest($request);
        $user = $token?->tokenable;

        if (! $user instanceof User || ! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->userCanManagePlatform($user) || ! $token->can('manage-platform')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }

    private function userCanManagePlatform(User $user): bool
    {
        return $user->is_platform_admin === true;
    }

    private function tokenFromRequest(Request $request): ?PersonalAccessToken
    {
        $token = $request->attributes->get('sanctum_personal_access_token');

        if ($token instanceof PersonalAccessToken) {
            return $token;
        }

        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return null;
        }

        $token = PersonalAccessToken::query()
            ->with('tokenable')
            ->where('token', hash('sha256', $this->plainTokenForHashing($plainTextToken)))
            ->first();

        if (! $token instanceof PersonalAccessToken || $token->isExpired()) {
            return null;
        }

        $request->attributes->set('sanctum_personal_access_token', $token);
        app()->instance('sanctum_personal_access_token', $token);

        return $token;
    }

    private function plainTokenForHashing(string $plainTextToken): string
    {
        if (str_contains($plainTextToken, '|')) {
            return (string) str($plainTextToken)->after('|');
        }

        return $plainTextToken;
    }
}
