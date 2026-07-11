<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    public function summary(Request $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $start = $request->date('start_date')?->toDateString() ?? now()->subDays(30)->toDateString();
        $end = $request->date('end_date')?->toDateString() ?? now()->toDateString();
        $orders = Order::query()->whereBetween('placed_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $count = (clone $orders)->count();
        $revenue = (int) (clone $orders)->sum('total_amount');

        return response()->json(['data' => ['orders_count' => $count, 'revenue_amount' => $revenue, 'aov_amount' => $count ? (int) round($revenue / $count) : 0, 'daily' => $this->analyticsService->getDailyMetrics($store, $start, $end)]]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }
}
