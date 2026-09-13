<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ReindexSearch;
use App\Models\SearchSetting;
use App\Services\SearchService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SearchAdminController extends Controller
{
    public function __construct(
        protected SearchService $search,
        protected CurrentStore $currentStore,
    ) {}

    public function reindex(int $storeId): JsonResponse
    {
        Gate::authorize('manage-search-settings');

        $store = $this->currentStore->get();

        abort_if($store === null || $store->getKey() !== $storeId, 404);

        ReindexSearch::dispatch($store->getKey());

        return response()->json([
            'message' => 'Reindex job queued.',
            'job_id' => 'job_reindex_'.Str::random(12),
            'status' => 'queued',
        ], 202);
    }

    public function status(int $storeId): JsonResponse
    {
        Gate::authorize('manage-search-settings');

        $store = $this->currentStore->get();

        abort_if($store === null || $store->getKey() !== $storeId, 404);

        $settings = SearchSetting::query()->where('store_id', $store->getKey())->first();

        return response()->json([
            'data' => [
                'store_id' => $store->getKey(),
                'index_status' => 'ready',
                'last_reindex_at' => $settings?->updated_at?->toIso8601String(),
                'last_reindex_duration_seconds' => null,
                'documents_count' => $this->search->documentCount($store),
                'pending_updates' => 0,
            ],
        ]);
    }
}
