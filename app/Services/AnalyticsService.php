<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'store_id' => $store->id,
            'type' => $type,
            'properties_json' => $properties,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * @return Collection<int, \App\Models\AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return \App\Models\AnalyticsDaily::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }
}
