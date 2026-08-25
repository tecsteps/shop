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
    public function handle(Request $request, Closure $next): Response
    {
        $store = app()->bound('current_store') ? app('current_store') : $this->resolve($request);

        if ($store === null) {
            abort(404, 'Store not found.');
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        if ($store->status === 'suspended' && $this->isStorefront($request)) {
            abort(503, 'This store is currently unavailable.');
        }

        return $next($request);
    }

    private function resolve(Request $request): ?Store
    {
        if ($this->isAdmin($request)) {
            return $this->resolveForAdmin($request);
        }

        return $this->resolveForStorefront($request);
    }

    private function isAdmin(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return str_starts_with($path, 'admin') || str_starts_with($path, 'api/admin');
    }

    private function isStorefront(Request $request): bool
    {
        return ! $this->isAdmin($request);
    }

    private function resolveForAdmin(Request $request): ?Store
    {
        $storeId = $request->route('storeId') ?? $request->session()->get('current_store_id');

        if (! $storeId) {
            return null;
        }

        $store = Store::find($storeId);

        if (! $store) {
            return null;
        }

        $user = $request->user();

        if ($user && ! $user->stores()->whereKey($store->id)->exists()) {
            abort(403, 'You do not have access to this store.');
        }

        return $store;
    }

    private function resolveForStorefront(Request $request): ?Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember('store_domain:'.$hostname, 300, function () use ($hostname) {
            return StoreDomain::where('hostname', $hostname)->value('store_id');
        });

        if (! $storeId) {
            return null;
        }

        return Store::find($storeId);
    }
}
