<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $context = 'storefront'): Response
    {
        $store = match ($context) {
            'admin' => $this->resolveAdmin($request),
            default => $this->resolveStorefront($request),
        };

        if (! $store) {
            if ($context === 'admin') {
                abort(404);
            }
            throw new NotFoundHttpException('Store not found for hostname '.$request->getHost());
        }

        if ($context === 'storefront' && $store->isSuspended()) {
            abort(503, 'Store temporarily unavailable');
        }

        app()->instance('current_store', $store);
        $request->attributes->set('current_store', $store);

        return $next($request);
    }

    protected function resolveStorefront(Request $request): ?Store
    {
        $hostname = strtolower($request->getHost());

        $storeId = Cache::remember("store_domain:{$hostname}", now()->addMinutes(5), function () use ($hostname): ?int {
            $domain = StoreDomain::query()->where('hostname', $hostname)->first();

            return $domain?->store_id;
        });

        if (! $storeId) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function resolveAdmin(Request $request): ?Store
    {
        $user = $request->user();
        $storeId = $request->session()->get('current_store_id');

        if (! $storeId && $user) {
            $storeId = $user->stores()->value('stores.id');
            if ($storeId) {
                $request->session()->put('current_store_id', $storeId);
            }
        }

        if (! $storeId) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            return null;
        }

        if ($user && ! $user->stores()->whereKey($store->id)->exists()) {
            abort(403, 'You are not a member of this store');
        }

        return $store;
    }
}
