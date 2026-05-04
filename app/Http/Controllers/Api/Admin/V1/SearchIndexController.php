<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchIndexController extends Controller
{
    public function reindex(Request $request, Store $store, SearchService $search): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $startedAt = microtime(true);
        $count = $search->reindex($store);
        $duration = (int) ceil(microtime(true) - $startedAt);

        $settings = $this->settings($store);
        $settings->forceFill([
            'updated_at' => now(),
        ])->save();

        return response()->json([
            'message' => __('Reindex completed.'),
            'job_id' => null,
            'status' => 'completed',
            'documents_count' => $count,
            'last_reindex_duration_seconds' => $duration,
        ], 202);
    }

    public function status(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $productCount = Product::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->count();

        $documentsCount = DB::table('products_fts')
            ->where('store_id', $store->getKey())
            ->distinct()
            ->count('product_id');

        $pendingUpdates = abs($productCount - $documentsCount);
        $settings = $this->settings($store);

        return response()->json([
            'data' => [
                'store_id' => $store->getKey(),
                'index_status' => $pendingUpdates === 0 ? 'ready' : 'stale',
                'last_reindex_at' => $settings->updated_at?->toIso8601String(),
                'last_reindex_duration_seconds' => 0,
                'documents_count' => $documentsCount,
                'pending_updates' => $pendingUpdates,
            ],
        ]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function settings(Store $store): SearchSettings
    {
        return SearchSettings::withoutGlobalScopes()->firstOrCreate([
            'store_id' => $store->getKey(),
        ]);
    }
}
