<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        if ($context === 'storefront') {
            return $this->resolveFromHostname($request, $next);
        }

        return $this->resolveFromSession($request, $next);
    }

    protected function resolveFromHostname(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            300,
            function () use ($hostname) {
                $domain = StoreDomain::where('hostname', $hostname)->first();

                return $domain?->store_id;
            }
        );

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
        View::share('currentStore', $store);

        return $next($request);
    }

    protected function resolveFromSession(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        $storeId = session('current_store_id');

        if (! $storeId) {
            $firstStore = $user->stores()->first();
            if (! $firstStore) {
                abort(403, 'No store access.');
            }
            $storeId = $firstStore->id;
            session(['current_store_id' => $storeId]);
        }

        $store = Store::find($storeId);
        if (! $store || ! $user->stores()->where('stores.id', $storeId)->exists()) {
            abort(403, 'No access to this store.');
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        return $next($request);
    }
}
