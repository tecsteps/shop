<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Storefront analytics: raw event ingestion and pre-aggregated metric reads.
 *
 * `track()` writes a single raw event (deduplicated per store by
 * `client_event_id`); {@see \App\Jobs\AggregateAnalytics} rolls raw events plus
 * order data into daily rows, which {@see getDailyMetrics()} reads back. All
 * `_amount` values are integers in minor units (cents).
 */
class AnalyticsService
{
    /**
     * Record a single analytics event for a store.
     *
     * Duplicate `client_event_id` values for the same store are silently dropped
     * (the existing event is returned) to make batch ingestion idempotent.
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?string $occurredAt = null,
    ): AnalyticsEvent {
        if ($clientEventId !== null) {
            $existing = AnalyticsEvent::query()
                ->where('store_id', $store->id)
                ->where('client_event_id', $clientEventId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return AnalyticsEvent::create([
            'store_id' => $store->id,
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurredAt,
        ]);
    }

    /**
     * Read pre-aggregated daily metrics for a store across an inclusive date
     * range (YYYY-MM-DD), ordered ascending by date.
     *
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Sum the daily rows in a range into a single totals array, computing a
     * blended average order value.
     *
     * @return array{orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    public function summarize(Store $store, string $startDate, string $endDate): array
    {
        $rows = $this->getDailyMetrics($store, $startDate, $endDate);

        $orders = (int) $rows->sum('orders_count');
        $revenue = (int) $rows->sum('revenue_amount');

        return [
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => (int) $rows->sum('visits_count'),
            'add_to_cart_count' => (int) $rows->sum('add_to_cart_count'),
            'checkout_started_count' => (int) $rows->sum('checkout_started_count'),
            'checkout_completed_count' => (int) $rows->sum('checkout_completed_count'),
        ];
    }

    /**
     * The event types accepted by the public ingestion endpoint.
     *
     * @return list<string>
     */
    public function ingestibleTypes(): array
    {
        return AnalyticsEventType::ingestibleValues();
    }
}
