<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveStoreFromHostname
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current_store')) {
            return $next($request);
        }

        $hostname = $request->getHost();

        $storeId = Cache::remember(
            "store_domain:{$hostname}",
            300,
            function () use ($hostname) {
                return StoreDomain::query()
                    ->where('hostname', $hostname)
                    ->value('store_id');
            }
        );

        if ($storeId) {
            $store = Store::query()->find($storeId);

            if ($store) {
                app()->instance('current_store', $store);
            }
        }

        return $next($request);
    }
}
