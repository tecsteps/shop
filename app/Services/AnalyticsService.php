<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function track(
        Store $store,
        AnalyticsEventType|string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
    ): ?AnalyticsEvent {
        $typeValue = $type instanceof AnalyticsEventType ? $type->value : $type;

        $attributes = [
            'store_id' => $store->getKey(),
            'type' => $typeValue,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => json_encode($properties === [] ? (object) [] : $properties, JSON_THROW_ON_ERROR),
            'client_event_id' => $clientEventId,
            'occurred_at' => now(),
            'created_at' => now(),
        ];

        if ($clientEventId !== null) {
            $inserted = DB::table('analytics_events')->insertOrIgnore($attributes);

            if ($inserted === 0) {
                return null;
            }

            return AnalyticsEvent::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('client_event_id', $clientEventId)
                ->first();
        }

        $id = DB::table('analytics_events')->insertGetId($attributes);

        return AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->find($id);
    }
}
