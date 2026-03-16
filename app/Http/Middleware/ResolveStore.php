<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            300,
            function () use ($hostname): ?int {
                $domain = StoreDomain::query()
                    ->where('hostname', $hostname)
                    ->first();

                return $domain?->store_id;
            }
        );

        if (! $storeId) {
            abort(404);
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    protected function resolveFromSession(Request $request, Closure $next): Response
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            return $next($request);
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            return $next($request);
        }

        if ($request->user() && ! $request->user()->stores()->where('stores.id', $store->id)->exists()) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }
}
