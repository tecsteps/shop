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
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS orders_count', ['checkout_completed'])
            ->selectRaw('COUNT(DISTINCT CASE WHEN type = ? THEN session_id END) AS visits_count', ['page_view'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS add_to_cart_count', ['add_to_cart'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS checkout_started_count', ['checkout_started'])
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $revenueByStore = [];

        $completedCheckouts = DB::table('analytics_events')
            ->select(['id', 'store_id', 'properties_json', 'payload'])
            ->whereRaw('date(COALESCE(occurred_at, created_at)) = ?', [$dateString])
            ->where('type', 'checkout_completed')
            ->orderBy('id')
            ->lazyById(500);

        foreach ($completedCheckouts as $checkout) {
            $properties = json_decode((string) $checkout->properties_json, true);

            if (! is_array($properties) || $properties === []) {
                $properties = json_decode((string) $checkout->payload, true);
            }

            $amount = is_array($properties)
                ? ($properties['order_total_amount'] ?? $properties['total_amount'] ?? $properties['total'] ?? null)
                : null;

            if (is_int($amount) || (is_string($amount) && filter_var($amount, FILTER_VALIDATE_INT) !== false)) {
                $storeId = (int) $checkout->store_id;
                $revenueByStore[$storeId] = ($revenueByStore[$storeId] ?? 0) + (int) $amount;
            }
        }

        $dailyMetrics = [];
        $storeIds = DB::table('stores')->orderBy('id')->pluck('id');

        foreach ($storeIds as $storeId) {
            $storeId = (int) $storeId;
            $metrics = $eventMetrics->get($storeId);
            $ordersCount = (int) ($metrics->orders_count ?? 0);
            $revenueAmount = (int) ($revenueByStore[$storeId] ?? 0);

            $dailyMetrics[] = [
                'store_id' => $storeId,
                'date' => $dateString,
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                'visits_count' => (int) ($metrics->visits_count ?? 0),
                'add_to_cart_count' => (int) ($metrics->add_to_cart_count ?? 0),
                'checkout_started_count' => (int) ($metrics->checkout_started_count ?? 0),
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
