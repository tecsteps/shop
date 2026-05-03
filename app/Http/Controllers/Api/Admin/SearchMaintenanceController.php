<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SearchMaintenanceController extends Controller
{
    public function reindex(Store $store, SearchService $search): JsonResponse
    {
        $startedAt = microtime(true);
        $documentsCount = $search->reindexStore($store);
        $durationSeconds = (int) ceil(microtime(true) - $startedAt);

        return response()->json([
            'message' => 'Reindex job queued.',
            'job_id' => 'job_reindex_'.Str::lower((string) Str::ulid()),
            'status' => 'queued',
            'data' => [
                'documents_count' => $documentsCount,
                'last_reindex_duration_seconds' => $durationSeconds,
            ],
        ], 202);
    }

    public function status(Store $store, SearchService $search): JsonResponse
    {
        return response()->json([
            'data' => $search->indexStatus($store),
        ]);
    }
}
