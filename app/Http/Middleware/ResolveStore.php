<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        if ($context === 'admin') {
            return $this->resolveFromSession($request, $next);
        }

        return $this->resolveFromHostname($request, $next);
    }

    protected function resolveFromHostname(Request $request, Closure $next): Response
    {
        $store = app('current_store');

        if (! $store) {
            abort(404);
        }

        if ($store->status->value === 'suspended') {
            abort(503, 'This store is currently unavailable');
        }

        return $next($request);
    }

    protected function resolveFromSession(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId) {
            $store = Store::find($storeId);

            if ($store && $user->stores()->where('stores.id', $store->id)->exists()) {
                $this->bindStore($store);

                return $next($request);
            }
        }

        // Fallback: pick the user's first store
        $store = $user->stores()->first();

        if (! $store) {
            abort(403);
        }

        $request->session()->put('current_store_id', $store->id);
        $this->bindStore($store);

        return $next($request);
    }

    protected function bindStore(Store $store): void
    {
        app()->instance('current_store', $store);
        View::share('currentStore', $store);
    }
}
