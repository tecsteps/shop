<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public ?string $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date
            ? Carbon::parse($this->date)->toDateString()
            : Carbon::yesterday()->toDateString();

        $startOfDay = $date.' 00:00:00';
        $endOfDay = $date.' 23:59:59';

        $storeIds = AnalyticsEvent::withoutGlobalScopes()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->distinct()
            ->pluck('store_id');

        foreach ($storeIds as $storeId) {
            $this->aggregateForStore($storeId, $date, $startOfDay, $endOfDay);
        }
    }

    private function aggregateForStore(int $storeId, string $date, string $startOfDay, string $endOfDay): void
    {
        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();

        $visitsCount = $events
            ->whereNotNull('session_id')
            ->pluck('session_id')
            ->unique()
            ->count();

        $this->upsertMetric($storeId, $date, 'visits_count', $visitsCount);

        $eventTypeCounts = $events->groupBy('event_type')->map->count();

        $this->upsertMetric($storeId, $date, 'page_views', (int) ($eventTypeCounts['page_view'] ?? 0));
        $this->upsertMetric($storeId, $date, 'add_to_cart_count', (int) ($eventTypeCounts['add_to_cart'] ?? 0));
        $this->upsertMetric($storeId, $date, 'checkout_started_count', (int) ($eventTypeCounts['checkout_started'] ?? 0));

        $completedEvents = $events->where('event_type', 'checkout_completed');
        $ordersCount = $completedEvents->count();
        $this->upsertMetric($storeId, $date, 'orders_count', $ordersCount);

        $totalRevenue = $completedEvents->sum(function ($event) {
            $properties = is_array($event->properties_json)
                ? $event->properties_json
                : json_decode($event->properties_json, true);

            return (int) ($properties['total_amount'] ?? 0);
        });

        $this->upsertMetric($storeId, $date, 'revenue_amount', (int) $totalRevenue);

        $aov = $ordersCount > 0 ? (int) round($totalRevenue / $ordersCount) : 0;
        $this->upsertMetric($storeId, $date, 'aov_amount', $aov);

        $productViews = $events->where('event_type', 'product_view');
        $productViewCounts = $productViews->groupBy(function ($event) {
            $properties = is_array($event->properties_json)
                ? $event->properties_json
                : json_decode($event->properties_json, true);

            return $properties['product_title'] ?? 'Unknown';
        })->map->count();

        foreach ($productViewCounts as $productTitle => $count) {
            $this->upsertMetric($storeId, $date, 'product_views', (int) $count, (string) $productTitle);
        }
    }

    private function upsertMetric(int $storeId, string $date, string $metric, int $value, ?string $dimension = null): void
    {
        $query = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('date', $date)
            ->where('metric', $metric);

        if ($dimension === null) {
            $query->whereNull('dimension');
        } else {
            $query->where('dimension', $dimension);
        }

        $existing = $query->first();

        if ($existing) {
            $existing->update(['value' => $value]);
        } else {
            AnalyticsDaily::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'date' => $date,
                'metric' => $metric,
                'value' => $value,
                'dimension' => $dimension,
            ]);
        }
    }
}
