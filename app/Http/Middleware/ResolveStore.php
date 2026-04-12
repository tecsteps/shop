<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $type = 'storefront'): Response
    {
        $store = match ($type) {
            'storefront' => $this->resolveFromHost($request),
            'admin' => $this->resolveFromSession($request),
            default => null,
        };

        if ($store === null) {
            throw new NotFoundHttpException('Store not found.');
        }

        if ($store->status === StoreStatus::Suspended) {
            throw new HttpException(503, 'Store is temporarily unavailable.');
        }

        app()->instance('current_store', $store);

        return $next($request);
    }

    protected function resolveFromHost(Request $request): ?Store
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            'store_domain:'.$hostname,
            now()->addMinutes(5),
            function () use ($hostname): ?int {
                $domain = StoreDomain::query()
                    ->where('hostname', $hostname)
                    ->first();

                return $domain?->store_id;
            }
        );

        if ($storeId === null) {
            return null;
        }

        return Store::query()->find($storeId);
    }

    protected function resolveFromSession(Request $request): ?Store
    {
        $storeId = $request->session()->get('current_store_id');

        if ($storeId === null) {
            return null;
        }

        $user = Auth::guard('web')->user();

        if ($user === null) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            return null;
        }

        $hasAccess = $store->users()
            ->where('users.id', $user->id)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        return $store;
    }
}
