<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/*') || $request->is('admin')) {
            return $this->resolveFromSession($request, $next);
        }

        return $this->resolveFromHostname($request, $next);
    }

    private function resolveFromHostname(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember("store_domain:{$hostname}", 300, function () use ($hostname) {
            return StoreDomain::where('hostname', $hostname)->value('store_id');
        });

        if (! $storeId) {
            abort(404);
        }

        $store = Store::find($storeId);

        if (! $store) {
            abort(404);
        }

        if ($store->status->value === 'suspended') {
            abort(503);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    private function resolveFromSession(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $storeId = session('current_store_id');

        if (! $storeId) {
            $firstStore = $user->stores()->first();
            if ($firstStore) {
                session(['current_store_id' => $firstStore->id]);
                $storeId = $firstStore->id;
            }
        }

        if ($storeId) {
            $store = Store::find($storeId);

            if ($store && $user->stores()->where('stores.id', $store->id)->exists()) {
                app()->instance('current_store', $store);
            } else {
                abort(403, 'You do not have access to this store.');
            }
        }

        return $next($request);
    }
}
