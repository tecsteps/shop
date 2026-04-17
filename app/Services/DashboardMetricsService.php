<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    /**
     * Return metrics for a single day, preferring the rolled-up row and falling
     * back to a live aggregation over analytics_events + orders when absent.
     *
     * @return array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    public function forDay(Store $store, ?CarbonImmutable $date = null): array
    {
        $date = ($date ?? CarbonImmutable::now())->startOfDay();
        $dateStr = $date->toDateString();

        $row = AnalyticsDaily::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('date', $dateStr)
            ->first();

        if ($row !== null) {
            return [
                'date' => $dateStr,
                'orders_count' => $row->orders_count,
                'revenue_amount' => $row->revenue_amount,
                'aov_amount' => $row->aov_amount,
                'visits_count' => $row->visits_count,
                'add_to_cart_count' => $row->add_to_cart_count,
                'checkout_started_count' => $row->checkout_started_count,
                'checkout_completed_count' => $row->checkout_completed_count,
            ];
        }

        return $this->computeForDay($store, $date);
    }

    /**
     * @return array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    public function computeForDay(Store $store, CarbonImmutable $date): array
    {
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        $counts = DB::table('analytics_events')
            ->selectRaw('type, COUNT(*) AS c, COUNT(DISTINCT session_id) AS sessions')
            ->where('store_id', $store->getKey())
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $visits = (int) ($counts->get(AnalyticsEventType::PageView->value)->sessions ?? 0);
        $addToCart = (int) ($counts->get(AnalyticsEventType::AddToCart->value)->c ?? 0);
        $checkoutStarted = (int) ($counts->get(AnalyticsEventType::CheckoutStarted->value)->c ?? 0);
        $checkoutCompleted = (int) ($counts->get(AnalyticsEventType::CheckoutCompleted->value)->c ?? 0);

        $ordersCount = 0;
        $revenue = 0;

        if (Schema::hasTable('orders')) {
            $orderAgg = DB::table('orders')
                ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS rev')
                ->where('store_id', $store->getKey())
                ->whereBetween('created_at', [$start, $end])
                ->first();

            $ordersCount = (int) ($orderAgg->cnt ?? 0);
            $revenue = (int) ($orderAgg->rev ?? 0);
        }

        $aov = $ordersCount > 0 ? (int) floor($revenue / $ordersCount) : 0;

        return [
            'date' => $date->toDateString(),
            'orders_count' => $ordersCount,
            'revenue_amount' => $revenue,
            'aov_amount' => $aov,
            'visits_count' => $visits,
            'add_to_cart_count' => $addToCart,
            'checkout_started_count' => $checkoutStarted,
            'checkout_completed_count' => $checkoutCompleted,
        ];
    }

    /**
     * Upsert the analytics_daily row for the given store/date.
     */
    public function rollupDay(Store $store, CarbonImmutable $date): AnalyticsDaily
    {
        $metrics = $this->computeForDay($store, $date);

        DB::table('analytics_daily')->upsert([
            [
                'store_id' => $store->getKey(),
                'date' => $metrics['date'],
                'orders_count' => $metrics['orders_count'],
                'revenue_amount' => $metrics['revenue_amount'],
                'aov_amount' => $metrics['aov_amount'],
                'visits_count' => $metrics['visits_count'],
                'add_to_cart_count' => $metrics['add_to_cart_count'],
                'checkout_started_count' => $metrics['checkout_started_count'],
                'checkout_completed_count' => $metrics['checkout_completed_count'],
            ],
        ], ['store_id', 'date'], [
            'orders_count',
            'revenue_amount',
            'aov_amount',
            'visits_count',
            'add_to_cart_count',
            'checkout_started_count',
            'checkout_completed_count',
        ]);

        /** @var AnalyticsDaily $row */
        $row = AnalyticsDaily::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('date', $metrics['date'])
            ->first();

        return $row;
    }
}
