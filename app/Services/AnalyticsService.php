<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * @var list<string>
     */
    private array $supportedTypes = [
        'page_view',
        'product_view',
        'add_to_cart',
        'remove_from_cart',
        'checkout_started',
        'checkout_completed',
        'search',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?\DateTimeInterface $occurredAt = null,
    ): bool {
        if (! in_array($type, $this->supportedTypes, true)) {
            return false;
        }

        $clientEventId = $clientEventId ?? ($properties['client_event_id'] ?? null);

        try {
            AnalyticsEvent::query()->create([
                'store_id' => $store->id,
                'type' => $type,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'client_event_id' => $clientEventId,
                'occurred_at' => $occurredAt,
                'created_at' => now(),
            ]);

            return true;
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return false;
            }

            throw $exception;
        }
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }
}
