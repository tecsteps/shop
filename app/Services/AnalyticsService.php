<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Track a raw analytics event.
     */
    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null): void
    {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'event_type' => $type,
            'properties_json' => $properties,
        ]);
    }

    /**
     * Get daily metrics for a date range.
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top viewed products for a date range.
     *
     * @return Collection<int, object{product_id: int, view_count: int}>
     */
    public function getTopProducts(Store $store, string $startDate, string $endDate, int $limit = 10): Collection
    {
        return AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', 'product_view')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("json_extract(properties_json, '$.product_id') as product_id, count(*) as view_count")
            ->groupBy('product_id')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a sales summary for a date range.
     *
     * @return array{total_revenue: int, total_orders: int, average_order_value: int, total_visits: int}
     */
    public function getSalesSummary(Store $store, string $startDate, string $endDate): array
    {
        $dailyMetrics = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalRevenue = $dailyMetrics->sum('revenue_amount');
        $totalOrders = $dailyMetrics->sum('orders_count');
        $totalVisits = $dailyMetrics->sum('visits_count');
        $averageOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'total_visits' => $totalVisits,
        ];
    }
}
