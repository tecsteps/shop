<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\V1\StoreAnalyticsEventsRequest;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController extends Controller
{
    public function store(StoreAnalyticsEventsRequest $request, AnalyticsService $analytics): JsonResponse
    {
        $result = $analytics->trackBatch($this->currentStore(), $request->validated('events'));

        return response()->json($result, 202);
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
