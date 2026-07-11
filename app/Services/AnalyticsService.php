<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AnalyticsService
{
    /** @param array<string, mixed> $properties */
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?CarbonInterface $occurredAt = null,
    ): void {
        $eventType = AnalyticsEventType::tryFrom($type)
            ?? throw new InvalidArgumentException("Unsupported analytics event type [{$type}].");

        try {
            AnalyticsEvent::withoutGlobalScope(StoreScope::class)->create([
                'store_id' => $store->id,
                'type' => $eventType,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'client_event_id' => $clientEventId,
                'occurred_at' => $occurredAt ?? now(),
            ]);
        } catch (UniqueConstraintViolationException) {
        }
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }
}
