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
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            abort(403);
        }

        $store = Store::find($storeId);

        if (! $store) {
            abort(403);
        }

        $user = $request->user();

        if (! $user || ! $user->stores()->where('stores.id', $store->id)->exists()) {
            abort(403);
        }

        $this->bindStore($store);

        return $next($request);
    }

    protected function bindStore(Store $store): void
    {
        app()->instance('current_store', $store);
        View::share('currentStore', $store);
    }
}
