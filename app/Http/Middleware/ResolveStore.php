<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next, string $type = 'storefront'): Response
    {
        return $type === 'admin'
            ? $this->resolveFromSession($request, $next)
            : $this->resolveFromHostname($request, $next);
    }

    private function resolveFromHostname(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();

        $storeId = Cache::remember(
            'store_domain:'.$hostname,
            now()->addMinutes(5),
            fn () => StoreDomain::where('hostname', $hostname)->value('store_id')
                ?? Store::where('handle', $this->extractSubdomain($hostname))->value('id'),
        );

        if (! $storeId) {
            $storeId = Store::orderBy('id')->value('id');
        }

        if (! $storeId) {
            abort(404, 'Store not found for hostname '.$hostname);
        }

        $store = Store::find($storeId);
        if (! $store) {
            abort(404);
        }

        if ($store->status === StoreStatus::Suspended) {
            abort(503);
        }

        $this->bindStore($store);

        return $next($request);
    }

    private function resolveFromSession(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect('/admin/login');
        }

        $storeId = $request->session()->get('current_store_id');
        if (! $storeId) {
            $storeId = DB::table('store_users')
                ->where('user_id', $user->id)
                ->value('store_id');

            if ($storeId) {
                $request->session()->put('current_store_id', $storeId);
            }
        }

        if (! $storeId) {
            abort(403, 'No store assigned to your account.');
        }

        $hasAccess = DB::table('store_users')
            ->where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }

        $store = Store::find($storeId);
        if (! $store) {
            abort(403);
        }

        $this->bindStore($store);

        return $next($request);
    }

    private function bindStore(Store $store): void
    {
        app()->instance('current_store', $store);
        View::share('currentStore', $store);
    }

    private function extractSubdomain(string $hostname): ?string
    {
        $parts = explode('.', $hostname);

        return count($parts) >= 3 ? $parts[0] : null;
    }
}
