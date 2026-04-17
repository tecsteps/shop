<?php

namespace App\Http\Middleware;

use App\Support\Tenant\CurrentStore;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $store = $this->resolveCurrentStore($request);

        if ($store === null) {
            abort(500, 'Store context is not available.');
        }

        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            abort(403, 'You do not have access to this store.');
        }

        $storeUser = DB::table('store_users')
            ->where('store_id', $store->id)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($storeUser === null) {
            abort(403, 'You do not have access to this store.');
        }

        $allowedRoles = $this->normalizeRoles(array_values($roles));
        $role = is_string($storeUser->role ?? null) ? strtolower($storeUser->role) : null;

        if ($allowedRoles !== [] && ($role === null || ! in_array($role, $allowedRoles, true))) {
            abort(403, 'Insufficient permissions.');
        }

        $request->attributes->set('store_user', $storeUser);

        return $next($request);
    }

    private function resolveCurrentStore(Request $request): ?CurrentStore
    {
        $attributeStore = $request->attributes->get('current_store');

        if ($attributeStore instanceof CurrentStore) {
            return $attributeStore;
        }

        if (app()->bound(CurrentStore::class)) {
            $containerStore = app(CurrentStore::class);

            if ($containerStore instanceof CurrentStore) {
                return $containerStore;
            }
        }

        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        if ($store instanceof CurrentStore) {
            return $store;
        }

        if (is_array($store)) {
            /** @var array<string, mixed> $normalized */
            $normalized = [];

            foreach ($store as $key => $value) {
                if (is_string($key)) {
                    $normalized[$key] = $value;
                }
            }

            return CurrentStore::fromRecord($normalized);
        }

        if (is_object($store)) {
            return CurrentStore::fromRecord($store);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $roles
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        return array_values(array_filter(array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles,
        )));
    }
}
