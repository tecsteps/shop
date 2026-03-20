<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public const VALID_EVENT_TYPES = [
        'page_view',
        'product_view',
        'add_to_cart',
        'remove_from_cart',
        'checkout_started',
        'checkout_completed',
        'search',
    ];

    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null, ?string $clientEventId = null, ?string $occurredAt = null): void
    {
        if (! in_array($type, self::VALID_EVENT_TYPES)) {
            return;
        }

        // Silently skip duplicates (unique constraint on store_id + client_event_id)
        if ($clientEventId) {
            $exists = AnalyticsEvent::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('client_event_id', $clientEventId)
                ->exists();

            if ($exists) {
                return;
            }
        }

        AnalyticsEvent::create([
            'store_id' => $store->id,
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurredAt ? \Carbon\Carbon::parse($occurredAt) : now(),
            'created_at' => now(),
        ]);
    }

    public function trackBatch(Store $store, array $events): void
    {
        foreach ($events as $event) {
            $this->track(
                $store,
                $event['type'] ?? '',
                $event['properties'] ?? [],
                $event['session_id'] ?? null,
                null,
                $event['client_event_id'] ?? null,
                $event['occurred_at'] ?? null,
            );
        }
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date')
            ->get();
    }
}
