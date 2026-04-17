<?php

namespace App\Http\Middleware;

use App\Support\Tenant\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('stores') || ! Schema::hasTable('store_domains')) {
            return $next($request);
        }

        $hostname = strtolower($request->getHost());
        $cacheKey = $this->cacheKey($hostname);

        /** @var array{id: int, status: string, handle: string|null, name: string|null}|null $storeData */
        $storeData = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): ?array => $this->lookupStore($hostname),
        );

        if ($storeData === null) {
            abort(404, 'Store not found.');
        }

        $store = CurrentStore::fromRecord($storeData);

        if ($store->isSuspended()) {
            abort(503, 'This store is currently unavailable.');
        }

        $request->attributes->set('current_store', $store);
        app()->instance('current_store', $store);
        app()->instance(CurrentStore::class, $store);
        View::share('currentStore', $store);

        return $next($request);
    }

    private function cacheKey(string $hostname): string
    {
        return sprintf('store_domain:%s', $hostname);
    }

    /**
     * @return array{id: int, status: string, handle: string|null, name: string|null}|null
     */
    private function lookupStore(string $hostname): ?array
    {
        $record = DB::table('store_domains')
            ->join('stores', 'stores.id', '=', 'store_domains.store_id')
            ->whereRaw('LOWER(store_domains.hostname) = ?', [$hostname])
            ->select([
                'stores.id',
                'stores.status',
                'stores.handle',
                'stores.name',
            ])
            ->first();

        if ($record === null) {
            return null;
        }

        return CurrentStore::fromRecord($record)->toArray();
    }
}
