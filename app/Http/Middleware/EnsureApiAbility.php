<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAbility
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        abort_unless($request->bearerToken() !== null && $user !== null, 401, 'A Sanctum bearer token is required.');

        $ability = $this->abilityFor($request, $user);

        abort_unless($ability !== null && $user->tokenCan($ability), 403, 'This token does not have the required ability.');

        return $next($request);
    }

    private function abilityFor(Request $request, User $user): ?string
    {
        $path = $request->path();
        $method = $request->method();

        if (Str::contains($path, '/platform/')) {
            abort_unless($user->isPlatformAdmin(), 403, 'Platform administration is restricted to platform administrators.');

            return 'manage-platform';
        }

        if (Str::endsWith($path, '/invites')) {
            return 'manage-platform';
        }

        if (Str::contains($path, '/exports/')) {
            return 'read-orders';
        }

        foreach (['products' => 'products', 'collections' => 'collections', 'orders' => 'orders', 'customers' => 'customers', 'discounts' => 'discounts'] as $segment => $resource) {
            if (Str::contains($path, '/'.$segment)) {
                return in_array($method, ['GET', 'HEAD'], true) ? 'read-'.$resource : 'write-'.$resource;
            }
        }

        if (Str::contains($path, '/themes')) {
            return in_array($method, ['GET', 'HEAD'], true) ? 'read-themes' : 'write-themes';
        }

        if (Str::contains($path, '/pages')) {
            return in_array($method, ['GET', 'HEAD'], true) ? 'read-content' : 'write-content';
        }

        if (Str::contains($path, '/shipping') || Str::contains($path, '/tax/')) {
            return in_array($method, ['GET', 'HEAD'], true) ? 'read-settings' : 'write-settings';
        }

        if (Str::contains($path, '/analytics')) {
            return 'read-analytics';
        }

        if (Str::contains($path, '/search')) {
            return in_array($method, ['GET', 'HEAD'], true) ? 'read-products' : 'write-products';
        }

        return null;
    }
}
