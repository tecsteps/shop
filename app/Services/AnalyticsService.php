<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function aggregatePreviousDay(): int
    {
        return $this->aggregateForDate(now('UTC')->subDay());
    }

    public function aggregateForDate(DateTimeInterface|string|null $date = null): int
    {
        $aggregationDate = $date === null
            ? now('UTC')->subDay()->startOfDay()
            : CarbonImmutable::parse($date, 'UTC')->utc()->startOfDay();
        $dateString = $aggregationDate->toDateString();

        $eventMetrics = DB::table('analytics_events')
            ->select('store_id')
            ->whereRaw('date(COALESCE(occurred_at, created_at)) = ?', [$dateString])
            ->selectRaw('COUNT(DISTINCT CASE WHEN type = ? THEN session_id END) AS visits_count', ['page_view'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS add_to_cart_count', ['add_to_cart'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS checkout_started_count', ['checkout_started'])
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $salesMetrics = DB::table('orders')
            ->select('store_id')
            ->whereDate('placed_at', $dateString)
            ->whereIn('financial_status', ['paid', 'partially_refunded'])
            ->selectRaw('COUNT(*) AS orders_count')
            ->selectRaw('SUM(MAX(0, total_amount - (SELECT COALESCE(SUM(refunds.amount), 0) FROM refunds WHERE refunds.order_id = orders.id AND refunds.status = ?))) AS revenue_amount', ['processed'])
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $dailyMetrics = [];
        $storeIds = DB::table('stores')->orderBy('id')->pluck('id');

        foreach ($storeIds as $storeId) {
            $storeId = (int) $storeId;
            $eventData = $eventMetrics->get($storeId);
            $salesData = $salesMetrics->get($storeId);
            $ordersCount = (int) ($salesData->orders_count ?? 0);
            $revenueAmount = (int) ($salesData->revenue_amount ?? 0);

            $dailyMetrics[] = [
                'store_id' => $storeId,
                'date' => $dateString,
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                'visits_count' => (int) ($eventData->visits_count ?? 0),
                'add_to_cart_count' => (int) ($eventData->add_to_cart_count ?? 0),
                'checkout_started_count' => (int) ($eventData->checkout_started_count ?? 0),
                'checkout_completed_count' => $ordersCount,
            ];
        }

        if ($dailyMetrics === []) {
            return 0;
        }

        DB::table('analytics_daily')->upsert(
            $dailyMetrics,
            ['store_id', 'date'],
            ['orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count'],
        );

        return count($dailyMetrics);
    }
}
