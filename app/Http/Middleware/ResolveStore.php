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
        $isAdminRequest = $this->isAdminRequest($request);
        $isAdminApiRequest = $request->is('api/admin/*');
        $store = $request->is('api/admin/*')
            ? $this->resolveAdminApiStore($request)
            : ($isAdminRequest ? $this->resolveAdminStore($request) : $this->resolveStorefrontStore($request));

        if (! $store instanceof Store) {
            if ($isAdminRequest && $request->user() && $request->user()->stores()->count() > 1) {
                return redirect()->route('admin.select-store');
            }

            abort(404);
        }

        if ($isAdminApiRequest && ! $request->user()->stores()->whereKey($store->id)->exists()) {
            abort(403);
        }

        if ($store->status === 'suspended') {
            if (! $isAdminRequest && ! $isAdminApiRequest) {
                abort(503, 'This storefront is temporarily unavailable.');
            }

            if (! $request->isMethodSafe()) {
                abort(403, 'This store is suspended and cannot be changed.');
            }
        }

        app()->instance('current_store', $store);
        view()->share('currentStore', $store);

        return $next($request);
    }

    private function isAdminRequest(Request $request): bool
    {
        if ($request->is('admin', 'admin/*')) {
            return true;
        }

        if (! str_starts_with($request->path(), 'livewire')) {
            return false;
        }

        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);

        return is_string($refererPath) && ($refererPath === '/admin' || str_starts_with($refererPath, '/admin/'));
    }

    private function resolveStorefrontStore(Request $request): ?Store
    {
        $hostname = strtolower($request->getHost());
        $storeId = Cache::remember("store-domain:{$hostname}", now()->addMinutes(5), function () use ($hostname): ?int {
            return StoreDomain::query()
                ->where('hostname', $hostname)
                ->where('type', 'storefront')
                ->value('store_id');
        });

        return $storeId ? Store::query()->find($storeId) : null;
    }

    private function resolveAdminStore(Request $request): ?Store
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $storeId = $request->session()->get('current_store_id');
        $membershipStores = $user->stores()->orderBy('stores.id')->get();

        if (! $storeId || ! $membershipStores->contains('id', (int) $storeId)) {
            $request->session()->forget('current_store_id');

            if ($membershipStores->count() > 1) {
                return null;
            }

            $storeId = $membershipStores->first()?->id;
        }

        if (! $storeId) {
            return null;
        }

        $request->session()->put('current_store_id', $storeId);

        return $membershipStores->firstWhere('id', (int) $storeId);
    }

    private function resolveAdminApiStore(Request $request): ?Store
    {
        $storeId = $request->route('storeId');

        return $storeId ? Store::query()->find($storeId) : null;
    }
}
