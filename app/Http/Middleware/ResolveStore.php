<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminRequest = $request->is('admin', 'admin/*', 'api/admin/*') || $request->routeIs('admin.*', 'api.admin.*');
        $store = $isAdminRequest
            ? $this->resolveAdminStore($request)
            : $this->resolveStorefrontStore($request);

        if ($store->status === StoreStatus::Suspended) {
            if (! $isAdminRequest) {
                abort(Response::HTTP_SERVICE_UNAVAILABLE, 'This store is currently unavailable.');
            }

            if (! $request->isMethodSafe()) {
                abort(Response::HTTP_FORBIDDEN, 'This suspended store cannot be modified.');
            }
        }

        app()->instance('current_store', $store);
        View::share('currentStore', $store);

        return $next($request);
    }

    private function resolveStorefrontStore(Request $request): Store
    {
        $hostname = mb_strtolower($request->getHost());
        $cacheKey = "store_domain:{$hostname}";

        $storeId = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): ?int => StoreDomain::query()
                ->where('hostname', $hostname)
                ->value('store_id'),
        );

        abort_if($storeId === null, Response::HTTP_NOT_FOUND, 'Store not found.');

        return Store::query()->findOrFail($storeId);
    }

    private function resolveAdminStore(Request $request): Store
    {
        $user = $request->user();
        $routeStore = $request->route('store');
        $storeId = $request->is('api/admin/*')
            ? ($routeStore instanceof Store ? $routeStore->id : $routeStore)
            : $request->session()->get('current_store_id');

        abort_unless($user instanceof User && is_numeric($storeId), Response::HTTP_FORBIDDEN);

        $store = Store::query()->findOrFail((int) $storeId);
        $hasAccess = $user->storeUsers()->where('store_id', $store->getKey())->exists();

        abort_unless($hasAccess, Response::HTTP_FORBIDDEN, 'You do not have access to this store.');

        return $store;
    }
}
