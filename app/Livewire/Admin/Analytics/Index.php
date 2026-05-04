<?php

namespace App\Livewire\Admin\Analytics;

use App\Enums\AnalyticsEventType;
use App\Enums\FinancialStatus;
use App\Enums\StoreUserRole;
use App\Models\AnalyticsEvent;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Support\Money;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public string $storeCurrency = 'EUR';

    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $conversionRate = 0.0;

    /**
     * @var list<array{date: string, label: string, revenue: int, orders: int}>
     */
    public array $salesChartData = [];

    public int $maxSalesChartAmount = 1;

    /**
     * @var list<array{title: string, units_sold: int, revenue: int, percentage: float}>
     */
    public array $topProducts = [];

    /**
     * @var list<array{source: string, sessions: int, orders: int, conversion_rate: float}>
     */
    public array $topReferrers = [];

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public function mount(AnalyticsService $analytics): void
    {
        $store = $this->store();

        $this->authorize('view', $store);
        abort_unless($this->canViewAnalytics($store), 403);

        $this->storeId = $store->getKey();
        $this->storeCurrency = $store->default_currency;

        $this->loadAnalytics($analytics);
    }

    public function updatedDateRange(AnalyticsService $analytics): void
    {
        $this->loadAnalytics($analytics);
    }

    public function updatedCustomStartDate(AnalyticsService $analytics): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics($analytics);
        }
    }

    public function updatedCustomEndDate(AnalyticsService $analytics): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics($analytics);
        }
    }

    public function updatedChannelFilter(AnalyticsService $analytics): void
    {
        $this->loadAnalytics($analytics);
    }

    public function updatedDeviceFilter(AnalyticsService $analytics): void
    {
        $this->loadAnalytics($analytics);
    }

    public function loadAnalytics(AnalyticsService $analytics): void
    {
        [$start, $end] = $this->currentRange();
        $store = $this->scopedStore();

        $totals = $analytics->totals($store, $start->toDateString(), $end->toDateString());

        $this->totalSales = $totals['revenue_amount'];
        $this->ordersCount = $totals['orders_count'];
        $this->averageOrderValue = $totals['aov_amount'];
        $this->conversionRate = $totals['visits_count'] > 0
            ? round(($totals['checkout_completed_count'] / $totals['visits_count']) * 100, 2)
            : 0.0;

        $daily = $analytics->getDailyMetrics($store, $start->toDateString(), $end->toDateString())->keyBy('date');

        $this->salesChartData = collect(CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay()))
            ->map(function (CarbonInterface $date) use ($daily): array {
                $metric = $daily->get($date->toDateString());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M j'),
                    'revenue' => (int) ($metric?->revenue_amount ?? 0),
                    'orders' => (int) ($metric?->orders_count ?? 0),
                ];
            })
            ->values()
            ->all();

        $this->maxSalesChartAmount = max(1, max(array_column($this->salesChartData, 'revenue') ?: [0]));
        $this->loadTopProducts($start, $end);
        $this->loadTopReferrers($start, $end);
    }

    public function exportCsv(AnalyticsService $analytics): void
    {
        $this->isExporting = true;
        $this->loadAnalytics($analytics);

        $lines = ['date,revenue_amount,orders_count'];

        foreach ($this->salesChartData as $point) {
            $lines[] = "{$point['date']},{$point['revenue']},{$point['orders']}";
        }

        $this->exportUrl = 'data:text/csv;charset=utf-8,'.rawurlencode(implode("\n", $lines));
        $this->isExporting = false;
    }

    #[Computed]
    public function formattedTotalSales(): string
    {
        return Money::format($this->totalSales, $this->storeCurrency);
    }

    #[Computed]
    public function formattedAov(): string
    {
        return Money::format($this->averageOrderValue, $this->storeCurrency);
    }

    public function render(): mixed
    {
        return view('livewire.admin.analytics.index')->layout('layouts.app', [
            'title' => __('Analytics'),
        ]);
    }

    private function loadTopProducts(CarbonInterface $start, CarbonInterface $end): void
    {
        $rows = OrderLine::query()
            ->selectRaw('order_lines.title_snapshot as title, sum(order_lines.quantity) as units_sold, sum(order_lines.total_amount) as revenue')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->storeId)
            ->whereBetween('orders.placed_at', [$start, $end])
            ->whereIn('orders.financial_status', [
                FinancialStatus::Paid->value,
                FinancialStatus::PartiallyRefunded->value,
            ])
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $totalRevenue = max(1, (int) $rows->sum('revenue'));

        $this->topProducts = $rows
            ->map(fn (OrderLine $line): array => [
                'title' => (string) $line->title,
                'units_sold' => (int) $line->units_sold,
                'revenue' => (int) $line->revenue,
                'percentage' => round(((int) $line->revenue / $totalRevenue) * 100, 1),
            ])
            ->all();
    }

    private function loadTopReferrers(CarbonInterface $start, CarbonInterface $end): void
    {
        $pageViews = $this->filteredEvents($start, $end, AnalyticsEventType::PageView);
        $orders = $this->filteredEvents($start, $end, AnalyticsEventType::CheckoutCompleted);
        $ordersBySource = $orders->groupBy(fn (AnalyticsEvent $event): string => $this->sourceFor($event));

        $this->topReferrers = $pageViews
            ->groupBy(fn (AnalyticsEvent $event): string => $this->sourceFor($event))
            ->map(function ($events, string $source) use ($ordersBySource): array {
                $sessions = $events->pluck('session_id')->filter()->unique()->count();
                $orders = $ordersBySource->get($source, collect())->count();

                return [
                    'source' => $source,
                    'sessions' => $sessions,
                    'orders' => $orders,
                    'conversion_rate' => $sessions > 0 ? round(($orders / $sessions) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('sessions')
            ->take(5)
            ->values()
            ->all();
    }

    private function filteredEvents(CarbonInterface $start, CarbonInterface $end, AnalyticsEventType $type): Collection
    {
        return AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->where('type', $type->value)
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->filter(fn (AnalyticsEvent $event): bool => $this->eventMatchesFilters($event))
            ->values();
    }

    private function eventMatchesFilters(AnalyticsEvent $event): bool
    {
        if ($this->channelFilter !== 'all' && data_get($event->properties_json, 'channel') !== $this->channelFilter) {
            return false;
        }

        return $this->deviceFilter === 'all'
            || data_get($event->properties_json, 'device') === $this->deviceFilter;
    }

    private function sourceFor(AnalyticsEvent $event): string
    {
        $referrer = (string) data_get($event->properties_json, 'referrer', 'direct');

        if ($referrer === '' || $referrer === 'direct') {
            return 'Direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return $host ? str_replace('www.', '', $host) : 'Direct';
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function currentRange(): array
    {
        return match ($this->dateRange) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'custom' => $this->customRange() ?? [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    private function customRange(): ?array
    {
        if (! $this->customStartDate || ! $this->customEndDate) {
            return null;
        }

        try {
            $start = Carbon::parse($this->customStartDate)->startOfDay();
            $end = Carbon::parse($this->customEndDate)->endOfDay();
        } catch (Throwable) {
            return null;
        }

        if ($end->lessThan($start)) {
            return [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->findOrFail($this->storeId);
    }

    private function canViewAnalytics(Store $store): bool
    {
        $role = auth()->user()?->roleForStoreId($store->getKey());

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true);
    }
}
