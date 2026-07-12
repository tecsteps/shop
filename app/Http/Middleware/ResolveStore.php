<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminApi = $request->is('api/admin/*');
        $store = $adminApi
            ? $this->fromAdminApiRoute($request)
            : ($request->is('admin', 'admin/*') ? $this->fromAdminSession($request) : $this->fromHostname($request));
        app()->instance('current_store', $store);
        $request->attributes->set('store', $store);

        if ($this->status($store) === 'suspended') {
            if ($request->is('admin', 'admin/*')) {
                $settingsRead = $request->isMethod('GET') && $request->is('admin/settings*');
                abort_unless($settingsRead, 403, 'This store is suspended.');
            } else {
                abort(503, 'This store is temporarily unavailable.');
            }
        }

        return $next($request);
    }

    private function fromHostname(Request $request): Store
    {
        $hostname = mb_strtolower($request->getHost());
        $storeId = Cache::remember("store_domain:{$hostname}", now()->addMinutes(5), fn (): ?int => StoreDomain::query()->where('hostname', $hostname)->value('store_id'));
        abort_if($storeId === null, 404);

        return Store::query()->findOrFail($storeId);
    }

    private function fromAdminSession(Request $request): Store
    {
        $user = Auth::guard('web')->user();
        abort_if($user === null, 403);

        $storeId = $request->session()->get('current_store_id');
        if ($storeId === null) {
            $storeId = $user->stores()->orderBy('stores.id')->value('stores.id');
            abort_if($storeId === null, 403);
            $request->session()->put('current_store_id', $storeId);
        }
        abort_unless($user->stores()->whereKey($storeId)->exists(), 403);

        return Store::query()->findOrFail($storeId);
    }

    private function fromAdminApiRoute(Request $request): Store
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $storeId = (int) $request->route('storeId');
        abort_unless($storeId > 0 && $user->stores()->whereKey($storeId)->exists(), 403);

        return Store::query()->findOrFail($storeId);
    }

    private function status(Store $store): string
    {
        return $store->status instanceof BackedEnum ? (string) $store->status->value : (string) $store->status;
    }
}
