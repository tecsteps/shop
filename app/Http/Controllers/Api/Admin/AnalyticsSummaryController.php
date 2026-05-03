<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsSummaryRequest;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsSummaryController extends Controller
{
    public function show(AnalyticsSummaryRequest $request, Store $store, AnalyticsService $analytics): JsonResponse
    {
        return response()->json([
            'data' => $analytics->summary(
                $store,
                (string) $request->validated('from'),
                (string) $request->validated('to'),
            ),
        ]);
    }
}
