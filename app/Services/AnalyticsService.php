<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AnalyticsService
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
    ): ?AnalyticsEvent {
        if (! in_array($type, ['page_view', 'product_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search'], true)) {
            throw new \InvalidArgumentException('Unsupported analytics event type.');
        }

        return AnalyticsEvent::withoutGlobalScopes()->firstOrCreate([
            'store_id' => $store->id,
            'client_event_id' => $clientEventId ?? (string) Str::uuid(),
        ], [
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /** @return Collection<int, AnalyticsDaily> */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()->where('store_id', $store->id)->whereBetween('date', [$startDate, $endDate])->orderBy('date')->get();
    }

    public function aggregate(Store $store, CarbonInterface|string $date): AnalyticsDaily
    {
        $date = is_string($date) ? $date : $date->toDateString();
        $events = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->id)->whereDate('occurred_at', $date)->get();
        $completed = $events->where('type', 'checkout_completed');
        $revenue = (int) $completed->sum(fn (AnalyticsEvent $event): int => (int) data_get($event->properties_json, 'total_amount', data_get($event->properties_json, 'total', 0)));
        $orders = $completed->count();

        DB::table('analytics_daily')->updateOrInsert(
            ['store_id' => $store->id, 'date' => $date],
            [
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                'visits_count' => $events->where('type', 'page_view')->pluck('session_id')->filter()->unique()->count(),
                'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                'checkout_completed_count' => $orders,
            ],
        );

        return AnalyticsDaily::withoutGlobalScopes()->where('store_id', $store->id)->whereDate('date', $date)->firstOrFail();
    }
}
