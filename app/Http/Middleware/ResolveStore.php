<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $store = $context === 'admin'
            ? $this->resolveForAdmin($request)
            : $this->resolveForStorefront($request);

        if (! $store) {
            abort(404, 'Store not found.');
        }

        /** @var \App\Enums\StoreStatus|string $status */
        $status = $store->status;
        $isSuspended = $status instanceof \App\Enums\StoreStatus
            ? $status === \App\Enums\StoreStatus::Suspended
            : $status === 'suspended';

        if ($isSuspended) {
            abort(503, 'Store is currently unavailable.');
        }

        app()->singleton('current_store', fn (): Store => $store);

        if ($context === 'storefront') {
            $request->session()->put('storefront_store_id', $store->id);
        }

        return $next($request);
    }

    private function resolveForStorefront(Request $request): ?Store
    {
        $hostname = $request->getHost();

        $cacheKey = 'store_domain:'.$hostname;

        $storeId = Cache::remember($cacheKey, 300, function () use ($hostname): ?int {
            $domain = StoreDomain::query()
                ->where('hostname', $hostname)
                ->first();

            return $domain?->store_id;
        });

        if (! $storeId) {
            return null;
        }

        /** @var Store|null */
        return Store::query()->find($storeId);
    }

    private function resolveForAdmin(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            return null;
        }

        /** @var Store|null $store */
        $store = Store::query()->find($storeId);

        if (! $store) {
            return null;
        }

        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $hasAccess = $user->stores()
            ->where('stores.id', $store->id)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        return $store;
    }
}
