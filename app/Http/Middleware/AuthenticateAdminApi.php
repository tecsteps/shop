<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
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

        app()->instance('current_store', $store);

        $token = $this->tokenFromRequest($request);
        $user = $token?->tokenable;

        if (! $user instanceof User || ! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ((int) $token->store_id !== $store->getKey()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->userCanAccessStore($user, $store, $abilities)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->hasAbilities($token, $abilities)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

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

    /**
     * @param  list<string>  $abilities
     */
    private function hasAbilities(PersonalAccessToken $token, array $abilities): bool
    {
        if ($abilities === []) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (! $token->can($ability)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $abilities
     */
    private function userCanAccessStore(User $user, Store $store, array $abilities): bool
    {
        $role = $user->roleForStore($store);

        if (! $role instanceof StoreUserRole) {
            return false;
        }

        if ($abilities === []) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (! $this->roleAllowsAbility($role, $ability)) {
                return false;
            }
        }

        return true;
    }

    private function roleAllowsAbility(StoreUserRole $role, string $ability): bool
    {
        return match ($ability) {
            'read-products',
            'read-orders',
            'read-customers',
            'read-collections',
            'read-discounts' => true,
            'write-products',
            'write-collections',
            'write-discounts',
            'read-content',
            'write-content' => in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true),
            'write-orders' => in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true),
            'read-analytics' => in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true),
            'read-settings',
            'write-settings',
            'write-themes',
            'manage-platform' => in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin], true),
            default => false,
        };
    }
}
