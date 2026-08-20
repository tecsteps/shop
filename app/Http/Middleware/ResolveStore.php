<?php

namespace App\Http\Middleware;

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $context = $context === 'storefront' && $this->isAdminRequest($request)
            ? 'admin'
            : $context;

        $store = $context === 'admin'
            ? $this->resolveAdminStore($request)
            : $this->resolveStorefrontStore($request);

        abort_unless($store instanceof Store, $context === 'admin' ? 403 : 404);

        if ($store->status === StoreStatus::Suspended) {
            if ($context === 'storefront') {
                abort(503, 'This store is currently unavailable.');
            }

            if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                abort(403);
            }
        }

        $binding = (string) config('tenancy.binding', 'current_store');
        app()->instance($binding, $store);
        View::share((string) config('tenancy.view_share', 'currentStore'), $store);

        return $next($request);
    }

    private function resolveStorefrontStore(Request $request): ?Store
    {
        $hostname = Str::lower(rtrim(trim($request->getHost()), '.'));
        $cacheKey = config('tenancy.cache_prefix', 'store-domains').':'.$hostname;

        $storeId = Cache::remember(
            $cacheKey,
            (int) config('tenancy.store_cache_ttl', 300),
            fn (): ?int => StoreDomain::query()
                ->where('hostname', $hostname)
                ->where('type', config('tenancy.storefront_domain_type', StoreDomainType::Storefront->value))
                ->value('store_id'),
        );

        return $storeId === null ? null : Store::query()->find($storeId);
    }

    private function resolveAdminStore(Request $request): ?Store
    {
        $storeId = $request->session()->get(config('tenancy.admin_session_key', 'current_store_id'));
        $user = $request->user('web') ?? $request->user();

        if ($storeId === null || $user === null) {
            return null;
        }

        return $user->stores()->whereKey($storeId)->first();
    }

    private function isAdminRequest(Request $request): bool
    {
        $prefix = trim((string) config('tenancy.admin_path_prefix', 'admin'), '/');

        return $request->is($prefix, $prefix.'/*') || $request->routeIs($prefix.'.*');
    }
}
