<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\FinancialStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\AnalyticsService;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnalyticsSummaryController extends Controller
{
    public function show(Request $request, Store $store, AnalyticsService $analytics): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'granularity' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', (string) $validated['from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', (string) $validated['to'])->endOfDay();

        if ($from->diffInDays($to) > 365) {
            throw ValidationException::withMessages([
                'to' => __('The selected date range may not be greater than 365 days.'),
            ]);
        }

        $totals = $analytics->totals($store, $from->toDateString(), $to->toDateString());

        return response()->json([
            'data' => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'summary' => [
                    'orders_count' => $totals['orders_count'],
                    'revenue_amount' => $totals['revenue_amount'],
                    'aov_amount' => $totals['aov_amount'],
                    'visits_count' => $totals['visits_count'],
                    'add_to_cart_count' => $totals['add_to_cart_count'],
                    'checkout_started_count' => $totals['checkout_started_count'],
                    'conversion_rate' => $totals['visits_count'] > 0 ? round($totals['checkout_completed_count'] / $totals['visits_count'], 4) : 0.0,
                    'currency' => $store->default_currency,
                ],
                'daily' => $this->metricRows($analytics, $store, $from, $to, $validated['granularity'] ?? 'day'),
                'top_products' => $this->topProducts($store, $from, $to),
            ],
        ]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    /**
     * @return list<array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int}>
     */
    private function metricRows(AnalyticsService $analytics, Store $store, CarbonInterface $from, CarbonInterface $to, string $granularity): array
    {
        $metrics = $analytics->getDailyMetrics($store, $from->toDateString(), $to->toDateString());

        if ($granularity === 'day') {
            $metricsByDate = $metrics->keyBy('date');

            return collect(CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay()))
                ->map(fn (CarbonInterface $date): array => $this->metricRow($date->toDateString(), collect([$metricsByDate->get($date->toDateString())])->filter()))
                ->values()
                ->all();
        }

        return $metrics
            ->groupBy(fn ($metric): string => $this->periodStart((string) $metric->date, $granularity))
            ->map(fn (Collection $rows, string $date): array => $this->metricRow($date, $rows))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $metrics
     * @return array{date: string, orders_count: int, revenue_amount: int, aov_amount: int, visits_count: int, add_to_cart_count: int, checkout_started_count: int}
     */
    private function metricRow(string $date, Collection $metrics): array
    {
        $orders = (int) $metrics->sum('orders_count');
        $revenue = (int) $metrics->sum('revenue_amount');

        return [
            'date' => $date,
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => (int) $metrics->sum('visits_count'),
            'add_to_cart_count' => (int) $metrics->sum('add_to_cart_count'),
            'checkout_started_count' => (int) $metrics->sum('checkout_started_count'),
        ];
    }

    private function periodStart(string $date, string $granularity): string
    {
        $date = Carbon::parse($date);

        return match ($granularity) {
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->startOfMonth()->toDateString(),
            default => $date->toDateString(),
        };
    }

    /**
     * @return list<array{product_id: int|null, title: string, units_sold: int, revenue_amount: int}>
     */
    private function topProducts(Store $store, CarbonInterface $from, CarbonInterface $to): array
    {
        return OrderLine::query()
            ->selectRaw('order_lines.product_id, order_lines.title_snapshot as title, sum(order_lines.quantity) as units_sold, sum(order_lines.total_amount) as revenue_amount')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $store->getKey())
            ->whereBetween('orders.placed_at', [$from, $to])
            ->whereIn('orders.financial_status', [
                FinancialStatus::Paid->value,
                FinancialStatus::PartiallyRefunded->value,
            ])
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue_amount')
            ->limit(10)
            ->get()
            ->map(fn (OrderLine $line): array => [
                'product_id' => $line->product_id === null ? null : (int) $line->product_id,
                'title' => (string) $line->title,
                'units_sold' => (int) $line->units_sold,
                'revenue_amount' => (int) $line->revenue_amount,
            ])
            ->all();
    }
}
