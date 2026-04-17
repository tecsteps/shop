<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'store_id' => $store->id,
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $properties['client_event_id'] ?? null,
            'occurred_at' => now(),
        ]);
    }

    /**
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
}
