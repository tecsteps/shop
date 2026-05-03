<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public const EVENT_TYPES = [
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
    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null, ?string $clientEventId = null, ?CarbonInterface $occurredAt = null): bool
    {
        if ($clientEventId === null) {
            AnalyticsEvent::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'type' => $type,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'occurred_at' => $occurredAt ?? now(),
            ]);

            return true;
        }

        $event = AnalyticsEvent::withoutGlobalScopes()->firstOrCreate(
            [
                'store_id' => $store->id,
                'client_event_id' => $clientEventId,
            ],
            [
                'type' => $type,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'occurred_at' => $occurredAt ?? now(),
            ],
        );

        return $event->wasRecentlyCreated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{accepted: int, rejected: int}
     */
    public function trackBatch(Store $store, array $events, ?int $customerId = null): array
    {
        $accepted = 0;

        foreach ($events as $event) {
            $created = $this->track(
                $store,
                (string) $event['type'],
                $event['properties'] ?? [],
                (string) $event['session_id'],
                $customerId,
                (string) $event['client_event_id'],
                Carbon::parse((string) $event['occurred_at']),
            );

            if ($created) {
                $accepted++;
            }
        }

        return [
            'accepted' => $accepted,
            'rejected' => 0,
        ];
    }

    /**
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Store $store, string $startDate, string $endDate): array
    {
        $daily = $this->getDailyMetrics($store, $startDate, $endDate);
        $orders = (int) $daily->sum('orders_count');
        $revenue = (int) $daily->sum('revenue_amount');
        $visits = (int) $daily->sum('visits_count');
        $checkoutCompleted = (int) $daily->sum('checkout_completed_count');

        return [
            'period' => [
                'from' => $startDate,
                'to' => $endDate,
            ],
            'summary' => [
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                'visits_count' => $visits,
                'add_to_cart_count' => (int) $daily->sum('add_to_cart_count'),
                'checkout_started_count' => (int) $daily->sum('checkout_started_count'),
                'checkout_completed_count' => $checkoutCompleted,
                'conversion_rate' => $visits > 0 ? round($checkoutCompleted / $visits, 4) : 0.0,
                'currency' => $store->default_currency,
            ],
            'daily' => $daily->map(fn (AnalyticsDaily $metric): array => [
                'date' => $metric->date->toDateString(),
                'orders_count' => $metric->orders_count,
                'revenue_amount' => $metric->revenue_amount,
                'aov_amount' => $metric->aov_amount,
                'visits_count' => $metric->visits_count,
                'add_to_cart_count' => $metric->add_to_cart_count,
                'checkout_started_count' => $metric->checkout_started_count,
                'checkout_completed_count' => $metric->checkout_completed_count,
            ])->values()->all(),
        ];
    }

    public function aggregateDate(Store $store, CarbonInterface $date): void
    {
        $dateString = $date->toDateString();
        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('occurred_at', $dateString)
            ->get();

        $ordersCount = $events->where('type', 'checkout_completed')->count();
        $revenue = (int) $events
            ->where('type', 'checkout_completed')
            ->sum(fn (AnalyticsEvent $event): int => (int) data_get($event->properties_json, 'total_amount', 0));

        DB::table('analytics_daily')->updateOrInsert(
            [
                'store_id' => $store->id,
                'date' => $dateString,
            ],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenue,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                'visits_count' => $events->where('type', 'page_view')->pluck('session_id')->filter()->unique()->count(),
                'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                'checkout_completed_count' => $ordersCount,
            ],
        );
    }
}
