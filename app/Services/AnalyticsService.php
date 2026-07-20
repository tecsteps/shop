<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Analytics event tracking and daily metric reads (spec 05 §14, spec 09
 * Phase 9.2). Raw events are inserted via track(); duplicates are silently
 * dropped on the (store_id, client_event_id) unique index.
 */
class AnalyticsService
{
    /**
     * Event types accepted by the ingestion API (spec 02 §2.6).
     *
     * @var list<string>
     */
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
     * Insert a raw event. A client_event_id that was already recorded for
     * the store is silently dropped (spec 05 §14.1 deduplication).
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?string $occurredAt = null,
    ): void {
        if ($clientEventId !== null && $this->isDuplicate($store, $clientEventId)) {
            return;
        }

        try {
            AnalyticsEvent::withoutGlobalScope(StoreScope::class)->create([
                'store_id' => $store->id,
                'type' => $type,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'properties_json' => $properties,
                'client_event_id' => $clientEventId,
                'occurred_at' => $occurredAt ?? now(),
            ]);
        } catch (QueryException $exception) {
            // A unique-constraint violation means a concurrent duplicate;
            // drop it silently. Anything else is a real failure.
            if ($clientEventId === null || ! str_contains($exception->getMessage(), 'UNIQUE')) {
                throw $exception;
            }
        }
    }

    /**
     * Track an event from a server-side commerce flow. Session and customer
     * are resolved from the current request when not given. Never throws:
     * analytics must never break commerce.
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackSafely(
        Store $store,
        string $type,
        array $properties = [],
        ?int $customerId = null,
        ?string $clientEventId = null,
    ): void {
        try {
            $this->track(
                $store,
                $type,
                $properties,
                $this->requestSessionId(),
                $customerId ?? $this->requestCustomerId(),
                $clientEventId,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Daily metrics for a date range (inclusive), keyed by date. Stored
     * analytics_daily rows win; days without a stored row fall back to live
     * aggregation of the raw events so dashboards are correct before the
     * nightly job has run (spec 05 §14.2).
     *
     * @return Collection<string, array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        $stored = AnalyticsDaily::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn (AnalyticsDaily $row): string => $row->date);

        $live = $this->aggregateRawByDay($store->id, $startDate, $endDate);

        $days = collect();
        $end = CarbonImmutable::parse($endDate);

        for ($date = CarbonImmutable::parse($startDate); $date->lte($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $row = $stored->get($key);

            $days->put($key, $row !== null
                ? [
                    'date' => $key,
                    'orders_count' => $row->orders_count,
                    'revenue_amount' => $row->revenue_amount,
                    'aov_amount' => $row->aov_amount,
                    'visits_count' => $row->visits_count,
                    'add_to_cart_count' => $row->add_to_cart_count,
                    'checkout_started_count' => $row->checkout_started_count,
                    'checkout_completed_count' => $row->checkout_completed_count,
                ]
                : $live->get($key, $this->emptyMetrics($key)));
        }

        return $days;
    }

    /**
     * Aggregate a store's raw events for a single day into the analytics_daily
     * metric shape (spec 05 §14.2).
     *
     * @return array{orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    public function aggregateForDate(int $storeId, string $date): array
    {
        $row = $this->metricSelects(
            AnalyticsEvent::withoutGlobalScope(StoreScope::class)
                ->where('store_id', $storeId)
                ->whereRaw('DATE(occurred_at) = ?', [$date]),
        )->first();

        $orders = (int) ($row->orders_count ?? 0);
        $revenue = (int) ($row->revenue_amount ?? 0);

        return [
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => (int) ($row->visits_count ?? 0),
            'add_to_cart_count' => (int) ($row->add_to_cart_count ?? 0),
            'checkout_started_count' => (int) ($row->checkout_started_count ?? 0),
            'checkout_completed_count' => (int) ($row->checkout_completed_count ?? 0),
        ];
    }

    /**
     * Live aggregation of raw events grouped by day for a range, keyed by
     * date, in the same shape as getDailyMetrics rows.
     *
     * @return Collection<string, array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}>
     */
    private function aggregateRawByDay(int $storeId, string $startDate, string $endDate): Collection
    {
        $rows = $this->metricSelects(
            AnalyticsEvent::withoutGlobalScope(StoreScope::class)
                ->where('store_id', $storeId)
                ->whereRaw('DATE(occurred_at) BETWEEN ? AND ?', [$startDate, $endDate]),
        )
            ->selectRaw('DATE(occurred_at) as date')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return $rows->map(function ($row): array {
            $orders = (int) $row->orders_count;
            $revenue = (int) $row->revenue_amount;

            return [
                'date' => $row->date,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                'visits_count' => (int) $row->visits_count,
                'add_to_cart_count' => (int) $row->add_to_cart_count,
                'checkout_started_count' => (int) $row->checkout_started_count,
                'checkout_completed_count' => (int) $row->checkout_completed_count,
            ];
        });
    }

    /**
     * Add the metric aggregate selects shared by the per-day and per-range
     * aggregation queries (spec 05 §14.2 metric definitions).
     *
     * @template TBuilder of \Illuminate\Database\Eloquent\Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function metricSelects(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->selectRaw("SUM(CASE WHEN type = 'checkout_completed' THEN 1 ELSE 0 END) as orders_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'checkout_completed' THEN CAST(json_extract(properties_json, '$.total') AS INTEGER) END), 0) as revenue_amount")
            ->selectRaw("COUNT(DISTINCT CASE WHEN type = 'page_view' THEN session_id END) as visits_count")
            ->selectRaw("SUM(CASE WHEN type = 'add_to_cart' THEN 1 ELSE 0 END) as add_to_cart_count")
            ->selectRaw("SUM(CASE WHEN type = 'checkout_started' THEN 1 ELSE 0 END) as checkout_started_count")
            ->selectRaw("SUM(CASE WHEN type = 'checkout_completed' THEN 1 ELSE 0 END) as checkout_completed_count");
    }

    /**
     * A zeroed metrics row for a day without any events.
     *
     * @return array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int, checkout_completed_count: int}
     */
    private function emptyMetrics(string $date): array
    {
        return [
            'date' => $date,
            'orders_count' => 0,
            'revenue_amount' => 0,
            'aov_amount' => 0,
            'visits_count' => 0,
            'add_to_cart_count' => 0,
            'checkout_started_count' => 0,
            'checkout_completed_count' => 0,
        ];
    }

    /**
     * Whether the client event ID was already recorded for the store.
     */
    private function isDuplicate(Store $store, string $clientEventId): bool
    {
        return AnalyticsEvent::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->where('client_event_id', $clientEventId)
            ->exists();
    }

    /**
     * The ID of the current HTTP session, when there is a started one.
     */
    private function requestSessionId(): ?string
    {
        try {
            $request = app('request');

            return $request instanceof Request && $request->hasSession(true)
                ? $request->session()->getId()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The ID of the authenticated storefront customer, when there is one.
     */
    private function requestCustomerId(): ?int
    {
        try {
            $id = auth('customer')->id();

            return $id === null ? null : (int) $id;
        } catch (\Throwable) {
            return null;
        }
    }
}
