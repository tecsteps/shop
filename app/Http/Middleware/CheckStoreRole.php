<?php

namespace App\Http\Middleware;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(app()->bound('current_store'), Response::HTTP_FORBIDDEN);

        /** @var Store $store */
        $store = app('current_store');
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN);

        $storeUser = $user->storeUsers()
            ->where('store_id', $store->getKey())
            ->first();

        abort_if($storeUser === null, Response::HTTP_FORBIDDEN, 'You do not have access to this store.');

        if ($roles !== []) {
            $allowedRoles = array_values(array_filter(array_map(
                static fn (string $role): ?StoreUserRole => StoreUserRole::tryFrom($role),
                $roles,
            )));

            abort_unless(
                in_array($storeUser->role, $allowedRoles, true),
                Response::HTTP_FORBIDDEN,
                'Insufficient permissions.',
            );
        }

        $request->attributes->set('store_user', $storeUser);

        return $next($request);
    }
}
