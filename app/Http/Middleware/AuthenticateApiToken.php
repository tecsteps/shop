<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $requiredAbility = null): Response
    {
        $existingToken = $request->attributes->get('api_token');

        if ($existingToken instanceof PersonalAccessToken) {
            $abilities = $existingToken->abilities ?? [];

            if (! $this->tokenMatchesStore($existingToken, $request, $abilities)) {
                return response()->json(['message' => 'This token is not authorized for the requested store.'], 403);
            }

            if ($requiredAbility && ! in_array($requiredAbility, $abilities, true) && ! in_array('*', $abilities, true)) {
                return response()->json(['message' => 'This token is missing the required ability.'], 403);
            }

            return $next($request);
        }

        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = PersonalAccessToken::query()
            ->where('token', hash('sha256', $plainTextToken))
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $token || ! $token->tokenable instanceof \App\Models\User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $abilities = $token->abilities ?? [];

        if (! $this->tokenMatchesStore($token, $request, $abilities)) {
            return response()->json(['message' => 'This token is not authorized for the requested store.'], 403);
        }

        if ($requiredAbility && ! in_array($requiredAbility, $abilities, true) && ! in_array('*', $abilities, true)) {
            return response()->json(['message' => 'This token is missing the required ability.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->tokenable);
        $request->setUserResolver(fn () => $token->tokenable);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    /** @param list<string> $abilities */
    private function tokenMatchesStore(PersonalAccessToken $token, Request $request, array $abilities): bool
    {
        if (! $request->is('api/admin/*')) {
            return true;
        }

        if ($request->is('api/admin/v1/platform/*')) {
            return $token->store_id === null && in_array('manage-platform', $abilities, true);
        }

        $requestedStoreId = $request->route('storeId');

        if ($requestedStoreId === null) {
            return false;
        }

        if ($token->store_id === null) {
            return in_array('manage-platform', $abilities, true);
        }

        return (int) $token->store_id === (int) $requestedStoreId;
    }
}
