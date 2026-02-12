<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Support\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $store = $context === 'admin'
            ? $this->resolveForAdmin($request)
            : $this->resolveForStorefront($request);

        if (! $store && app()->environment(['local', 'testing'])) {
            $store = Store::query()->first();
        }

        if (! $store) {
            abort(404);
        }

        if ($context === 'storefront' && $store->status === StoreStatus::Suspended) {
            abort(503);
        }

        if ($context === 'admin') {
            $this->ensureAdminCanAccessStore($request, $store);
        }

        $this->bindCurrentStore($request, $store);

        return $next($request);
    }

    protected function resolveForStorefront(Request $request): ?Store
    {
        $hostname = strtolower($request->getHost());

        $storeId = Cache::remember(
            "store-domain:{$hostname}",
            now()->addMinutes(5),
            fn (): ?int => StoreDomain::query()
                ->whereRaw('lower(hostname) = ?', [$hostname])
                ->value('store_id')
        );

        if (! $storeId) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function resolveForAdmin(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function ensureAdminCanAccessStore(Request $request, Store $store): void
    {
        $user = $request->user('web');

        if (! $user) {
            return;
        }

        $hasAccess = $store->users()
            ->whereKey($user->id)
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }
    }

    protected function bindCurrentStore(Request $request, Store $store): void
    {
        app(CurrentStore::class)->set($store);
        app()->instance('current_store', $store);
        app()->instance('current_store_id', $store->id);

        $request->attributes->set('current_store', $store);
    }
}
