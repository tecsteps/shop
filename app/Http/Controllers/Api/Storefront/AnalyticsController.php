<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AnalyticsEventsRequest;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function store(AnalyticsEventsRequest $request, AnalyticsService $analytics): JsonResponse
    {
        $result = $analytics->trackBatch(
            $this->currentStore(),
            $request->validated('events'),
        );

        return response()->json($result, 202);
    }

    private function currentStore(): Store
    {
        return app('current_store');
    }
}
