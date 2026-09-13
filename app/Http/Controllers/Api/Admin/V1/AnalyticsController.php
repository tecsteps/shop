<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function __construct(
        protected CurrentStore $currentStore,
        protected AnalyticsService $analytics,
    ) {}

    public function summary(Request $request, int $storeId): JsonResponse
    {
        Gate::authorize('view-analytics');

        $validated = $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'granularity' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ]);

        $from = $validated['from'];
        $to = $validated['to'];

        if (now()->parse($from)->diffInDays(now()->parse($to)) > 365) {
            return response()->json([
                'message' => 'The date range must not exceed 365 days.',
                'errors' => ['to' => ['The date range must not exceed 365 days.']],
            ], 422);
        }

        $store = $this->currentStore->get();

        abort_if($store === null, 404);

        return response()->json([
            'data' => $this->analytics->summary($store, $from, $to, $validated['granularity'] ?? 'day'),
        ]);
    }
}
