<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
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
        ?CarbonInterface $occurredAt = null,
    ): bool {
        if ($clientEventId !== null && AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('client_event_id', $clientEventId)
            ->exists()) {
            return false;
        }

        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'type' => AnalyticsEventType::from($type),
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurredAt ?? now(),
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array{accepted: int, rejected: int}
     */
    public function trackBatch(Store $store, array $events): array
    {
        $accepted = 0;
        $rejected = 0;

        foreach ($events as $event) {
            $tracked = $this->track(
                $store,
                (string) $event['type'],
                $event['properties'] ?? [],
                $event['session_id'] ?? null,
                $event['customer_id'] ?? null,
                $event['client_event_id'] ?? null,
                isset($event['occurred_at']) ? Carbon::parse($event['occurred_at']) : null,
            );

            $tracked ? $accepted++ : $rejected++;
        }

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    public function aggregate(?CarbonInterface $date = null): int
    {
        $date = ($date ? Carbon::parse($date->toDateString(), 'UTC') : now('UTC')->subDay())->startOfDay();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $dateString = $date->toDateString();

        $storeIds = AnalyticsEvent::withoutGlobalScopes()
            ->whereBetween('occurred_at', [$start, $end])
            ->distinct()
            ->pluck('store_id');

        $rows = 0;

        foreach ($storeIds as $storeId) {
            $events = AnalyticsEvent::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->whereBetween('occurred_at', [$start, $end])
                ->get();

            $checkoutCompleted = $events->where('type', AnalyticsEventType::CheckoutCompleted);
            $ordersCount = $checkoutCompleted->count();
            $revenue = (int) $checkoutCompleted->sum(fn (AnalyticsEvent $event): int => (int) data_get($event->properties_json, 'total_amount', 0));

            DB::table('analytics_daily')->updateOrInsert(
                [
                    'store_id' => (int) $storeId,
                    'date' => $dateString,
                ],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                    'visits_count' => $events
                        ->where('type', AnalyticsEventType::PageView)
                        ->pluck('session_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'add_to_cart_count' => $events->where('type', AnalyticsEventType::AddToCart)->count(),
                    'checkout_started_count' => $events->where('type', AnalyticsEventType::CheckoutStarted)->count(),
                    'checkout_completed_count' => $ordersCount,
                ],
            );

            $rows++;
        }

        return $rows;
    }

    /**
     * @return array{orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    public function totals(Store $store, string $startDate, string $endDate): array
    {
        $metrics = $this->getDailyMetrics($store, $startDate, $endDate);
        $orders = (int) $metrics->sum('orders_count');
        $revenue = (int) $metrics->sum('revenue_amount');

        return [
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => (int) $metrics->sum('visits_count'),
            'add_to_cart_count' => (int) $metrics->sum('add_to_cart_count'),
            'checkout_started_count' => (int) $metrics->sum('checkout_started_count'),
            'checkout_completed_count' => (int) $metrics->sum('checkout_completed_count'),
        ];
    }
}
