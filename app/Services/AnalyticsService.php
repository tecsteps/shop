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
            'event_type' => $type,
            'properties_json' => $properties,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Get pre-aggregated daily metrics for a date range.
     *
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereNull('dimension')
            ->orderBy('date')
            ->get()
            ->groupBy(fn (AnalyticsDaily $row): string => $row->date->format('Y-m-d'));
    }

    /**
     * Get top viewed products by aggregating product_view events.
     *
     * @return Collection<int, object>
     */
    public function getTopProducts(Store $store, string $startDate, string $endDate, int $limit = 10): Collection
    {
        return AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', 'product_view')
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->selectRaw("json_extract(properties_json, '$.product_title') as product_title, COUNT(*) as views")
            ->groupByRaw("json_extract(properties_json, '$.product_title')")
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * Get conversion funnel data for a date range.
     *
     * @return array{page_views: int, product_views: int, add_to_cart: int, checkout_started: int, checkout_completed: int}
     */
    public function getConversionFunnel(Store $store, string $startDate, string $endDate): array
    {
        $counts = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->whereIn('event_type', ['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed'])
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        return [
            'page_views' => (int) ($counts['page_view'] ?? 0),
            'product_views' => (int) ($counts['product_view'] ?? 0),
            'add_to_cart' => (int) ($counts['add_to_cart'] ?? 0),
            'checkout_started' => (int) ($counts['checkout_started'] ?? 0),
            'checkout_completed' => (int) ($counts['checkout_completed'] ?? 0),
        ];
    }
}
