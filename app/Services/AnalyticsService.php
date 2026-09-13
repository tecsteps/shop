<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Analytics event ingestion and dashboard metrics per spec 05 section 14.
 */
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
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?CarbonImmutable $occurredAt = null,
    ): ?AnalyticsEvent {
        if (! in_array($type, self::EVENT_TYPES, true)) {
            return null;
        }

        if ($clientEventId !== null && AnalyticsEvent::query()
            ->where('store_id', $store->getKey())
            ->where('client_event_id', $clientEventId)
            ->exists()) {
            return null;
        }

        return AnalyticsEvent::query()->create([
            'store_id' => $store->getKey(),
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurredAt ?? now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Record a batch of endpoint-submitted events.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array{accepted: int, rejected: int}
     */
    public function recordBatch(Store $store, array $events): array
    {
        $accepted = 0;
        $rejected = 0;

        foreach ($events as $event) {
            $recorded = $this->track(
                $store,
                (string) ($event['type'] ?? ''),
                is_array($event['properties'] ?? null) ? $event['properties'] : [],
                isset($event['session_id']) ? (string) $event['session_id'] : null,
                isset($event['customer_id']) ? (int) $event['customer_id'] : null,
                isset($event['client_event_id']) ? (string) $event['client_event_id'] : null,
                isset($event['occurred_at']) ? CarbonImmutable::parse($event['occurred_at']) : null,
            );

            if ($recorded === null) {
                $rejected++;
            } else {
                $accepted++;
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected];
    }

    /**
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Dashboard/API summary shape per spec 02 section 3.11.
     *
     * @return array{period: array{from: string, to: string}, summary: array<string, mixed>, daily: array<int, array<string, mixed>>, top_products: array<int, array<string, mixed>>}
     */
    public function summary(Store $store, string $from, string $to, string $granularity = 'day'): array
    {
        $daily = $this->getDailyMetrics($store, $from, $to);

        $real = $this->realOrderMetrics($store, $from, $to);
        $ordersCount = $real['count'];
        $revenueAmount = $real['revenue'];
        $visitsCount = (int) $daily->sum('visits_count');

        $summary = [
            'orders_count' => $ordersCount,
            'revenue_amount' => $revenueAmount,
            'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
            'visits_count' => $visitsCount,
            'add_to_cart_count' => (int) $daily->sum('add_to_cart_count'),
            'checkout_started_count' => (int) $daily->sum('checkout_started_count'),
            'conversion_rate' => $visitsCount > 0 ? round($ordersCount / $visitsCount, 4) : 0.0,
            'currency' => $store->default_currency,
        ];

        return [
            'period' => ['from' => $from, 'to' => $to],
            'summary' => $summary,
            'daily' => $this->bucketizeWithRealOrders($store, $daily, $granularity, $from, $to),
            'top_products' => $this->topProducts($store, $from, $to),
        ];
    }

    /**
     * @return array<int, array{title: string, units_sold: int, revenue: int}>
     */
    public function topProductsByRevenue(Store $store, string $from, string $to, int $limit = 5): array
    {
        return $this->topProducts($store, $from, $to, $limit);
    }

    /**
     * @return array{visits: int, add_to_cart: int, checkout_started: int, checkout_completed: int}
     */
    public function funnel(Store $store, string $from, string $to): array
    {
        $daily = $this->getDailyMetrics($store, $from, $to);

        return [
            'visits' => (int) $daily->sum('visits_count'),
            'add_to_cart' => (int) $daily->sum('add_to_cart_count'),
            'checkout_started' => (int) $daily->sum('checkout_started_count'),
            'checkout_completed' => (int) $daily->sum('checkout_completed_count'),
        ];
    }

    /**
     * @return array<int, array{source: string, sessions: int, orders: int, conversion_rate: float}>
     */
    public function topReferrers(Store $store, string $from, string $to, int $limit = 10): array
    {
        $rows = AnalyticsEvent::query()
            ->where('store_id', $store->getKey())
            ->where('type', 'page_view')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['session_id', 'properties_json']);

        $grouped = [];

        foreach ($rows as $row) {
            $properties = is_array($row->properties_json) ? $row->properties_json : [];
            $referrer = (string) ($properties['referrer'] ?? '');
            $source = $referrer === '' ? 'Direct' : (parse_url($referrer, PHP_URL_HOST) ?: $referrer);

            if (! isset($grouped[$source])) {
                $grouped[$source] = ['sessions' => [], 'orders' => 0];
            }

            if ($row->session_id !== null) {
                $grouped[$source]['sessions'][$row->session_id] = true;
            }
        }

        $result = [];

        foreach ($grouped as $source => $data) {
            $sessions = count($data['sessions']);
            $result[] = [
                'source' => $source,
                'sessions' => $sessions,
                'orders' => 0,
                'conversion_rate' => 0.0,
            ];
        }

        usort($result, fn (array $a, array $b): int => $b['sessions'] <=> $a['sessions']);

        return array_slice($result, 0, $limit);
    }

    /**
     * @param  Collection<int, AnalyticsDaily>  $daily
     * @return array<int, array<string, mixed>>
     */
    protected function bucketize(Collection $daily, string $granularity): array
    {
        if ($granularity === 'day') {
            return $daily->map(fn (AnalyticsDaily $row): array => [
                'date' => $row->date,
                'orders_count' => (int) $row->orders_count,
                'revenue_amount' => (int) $row->revenue_amount,
                'aov_amount' => (int) $row->aov_amount,
                'visits_count' => (int) $row->visits_count,
                'add_to_cart_count' => (int) $row->add_to_cart_count,
                'checkout_started_count' => (int) $row->checkout_started_count,
            ])->all();
        }

        $buckets = [];

        foreach ($daily as $row) {
            $date = CarbonImmutable::parse($row->date);
            $key = $granularity === 'week'
                ? $date->startOfWeek()->toDateString()
                : $date->startOfMonth()->toDateString();

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'date' => $key,
                    'orders_count' => 0,
                    'revenue_amount' => 0,
                    'visits_count' => 0,
                    'add_to_cart_count' => 0,
                    'checkout_started_count' => 0,
                ];
            }

            $buckets[$key]['orders_count'] += (int) $row->orders_count;
            $buckets[$key]['revenue_amount'] += (int) $row->revenue_amount;
            $buckets[$key]['visits_count'] += (int) $row->visits_count;
            $buckets[$key]['add_to_cart_count'] += (int) $row->add_to_cart_count;
            $buckets[$key]['checkout_started_count'] += (int) $row->checkout_started_count;
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['aov_amount'] = $bucket['orders_count'] > 0
                ? intdiv($bucket['revenue_amount'], $bucket['orders_count'])
                : 0;
        }

        return array_values($buckets);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function topProducts(Store $store, string $from, string $to, int $limit = 5): array
    {
        $rows = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $store->getKey())
            ->whereBetween('orders.placed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc(DB::raw('SUM(order_lines.total_amount)'))
            ->limit($limit)
            ->get([
                'order_lines.product_id',
                'order_lines.title_snapshot',
                DB::raw('SUM(order_lines.quantity) as units_sold'),
                DB::raw('SUM(order_lines.total_amount) as revenue_amount'),
            ]);

        return $rows->map(fn ($row): array => [
            'product_id' => (int) $row->product_id,
            'title' => (string) $row->title_snapshot,
            'units_sold' => (int) $row->units_sold,
            'revenue_amount' => (int) $row->revenue_amount,
            'revenue' => (int) $row->revenue_amount,
        ])->all();
    }

    public function ordersCount(Store $store, string $from, string $to): int
    {
        return Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->count();
    }

    /**
     * @return array{count: int, revenue: int}
     */
    protected function realOrderMetrics(Store $store, string $from, string $to): array
    {
        $row = Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS rev')
            ->first();

        return [
            'count' => (int) ($row?->cnt ?? 0),
            'revenue' => (int) ($row?->rev ?? 0),
        ];
    }

    /**
     * @param  Collection<int, AnalyticsDaily>  $daily
     * @return array<int, array<string, mixed>>
     */
    protected function bucketizeWithRealOrders(Store $store, Collection $daily, string $granularity, string $from, string $to): array
    {
        $buckets = $this->bucketize($daily, $granularity);

        $orders = Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['placed_at', 'total_amount']);

        $byBucket = [];

        foreach ($orders as $order) {
            $date = CarbonImmutable::parse($order->placed_at);
            $key = $granularity === 'day'
                ? $date->toDateString()
                : ($granularity === 'week'
                    ? $date->startOfWeek()->toDateString()
                    : $date->startOfMonth()->toDateString());

            if (! isset($byBucket[$key])) {
                $byBucket[$key] = ['count' => 0, 'revenue' => 0];
            }

            $byBucket[$key]['count']++;
            $byBucket[$key]['revenue'] += (int) $order->total_amount;
        }

        foreach ($buckets as $index => $bucket) {
            $real = $byBucket[$bucket['date']] ?? ['count' => 0, 'revenue' => 0];
            $buckets[$index]['orders_count'] = $real['count'];
            $buckets[$index]['revenue_amount'] = $real['revenue'];
            $buckets[$index]['aov_amount'] = $real['count'] > 0 ? intdiv($real['revenue'], $real['count']) : 0;
        }

        return $buckets;
    }
}
