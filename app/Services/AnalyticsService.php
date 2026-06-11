<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * The event types accepted by the analytics pipeline (spec 05 section 14).
     *
     * @var list<string>
     */
    public const array EVENT_TYPES = [
        'page_view',
        'product_view',
        'add_to_cart',
        'remove_from_cart',
        'checkout_started',
        'checkout_completed',
        'search',
    ];

    /**
     * Record a raw analytics event. Events carrying a client_event_id that
     * was already recorded for the store are silently dropped (deduplication
     * via the unique index on store_id + client_event_id).
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
    ): ?AnalyticsEvent {
        if (! in_array($type, self::EVENT_TYPES, true)) {
            return null;
        }

        try {
            return AnalyticsEvent::query()->create([
                'store_id' => $store->getKey(),
                'type' => $type,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'client_event_id' => $clientEventId,
                'occurred_at' => $occurredAt ?? now(),
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /**
     * Read pre-aggregated daily metrics for an inclusive ISO date range.
     *
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::query()
            ->forStoreBetween($store, $startDate, $endDate)
            ->get();
    }

    /**
     * Count events per type for a store within a created_at range. Used by
     * the conversion funnel visualizations.
     *
     * @return array<string, int>
     */
    public function eventCountsBetween(Store $store, string $start, string $end): array
    {
        $counts = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $visits = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('type', 'page_view')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->distinct()
            ->count('session_id');

        return [
            'visits' => $visits,
            'page_view' => (int) ($counts['page_view'] ?? 0),
            'product_view' => (int) ($counts['product_view'] ?? 0),
            'add_to_cart' => (int) ($counts['add_to_cart'] ?? 0),
            'remove_from_cart' => (int) ($counts['remove_from_cart'] ?? 0),
            'checkout_started' => (int) ($counts['checkout_started'] ?? 0),
            'checkout_completed' => (int) ($counts['checkout_completed'] ?? 0),
            'search' => (int) ($counts['search'] ?? 0),
        ];
    }
}
