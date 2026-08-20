<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /** @var array<int, string> */
    private array $eventTypes = ['page_view', 'product_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search'];

    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null, ?string $clientEventId = null): AnalyticsEvent
    {
        if (! in_array($type, $this->eventTypes, true)) {
            throw new \InvalidArgumentException('Unsupported analytics event type.');
        }

        if ($clientEventId !== null) {
            $existing = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->getKey())->where('client_event_id', $clientEventId)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return AnalyticsEvent::withoutGlobalScopes()->create(['store_id' => $store->getKey(), 'type' => $type, 'session_id' => $sessionId, 'customer_id' => $customerId, 'client_event_id' => $clientEventId, 'payload' => $properties]);
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return \App\Models\AnalyticsDaily::withoutGlobalScopes()->where('store_id', $store->getKey())->whereBetween('date', [$startDate, $endDate])->orderBy('date')->get();
    }
}
