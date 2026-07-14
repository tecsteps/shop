<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\AdminComponent;
use App\Models\AnalyticsEvent;
use App\Models\OrderLine;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Index extends AdminComponent
{
    public string $dateRange = '30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $conversionRate = 0.0;

    /** @var list<array<string, int|string>> */
    public array $salesChartData = [];

    /** @var list<array<string, int|float|string>> */
    public array $topProducts = [];

    /** @var list<array<string, int|float|string>> */
    public array $topReferrers = [];

    public int $visitsCount = 0;

    public int $addToCartCount = 0;

    public int $checkoutStartedCount = 0;

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public function mount(): void
    {
        $this->authorizeAnalytics();
        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->authorizeAnalytics();
        $this->loadAnalytics();
    }

    public function updatedChannelFilter(): void
    {
        $this->authorizeAnalytics();
        $this->loadAnalytics();
    }

    public function updatedDeviceFilter(): void
    {
        $this->authorizeAnalytics();
        $this->loadAnalytics();
    }

    public function updatedCustomStartDate(): void
    {
        $this->authorizeAnalytics();
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics();
        }
    }

    public function updatedCustomEndDate(): void
    {
        $this->authorizeAnalytics();
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics();
        }
    }

    public function loadAnalytics(): void
    {
        $this->authorizeAnalytics();
        $this->validate([
            'dateRange' => ['required', Rule::in(['today', '7_days', '30_days', 'custom'])],
            'customStartDate' => ['nullable', 'date'], 'customEndDate' => ['nullable', 'date', 'after_or_equal:customStartDate'],
            'channelFilter' => ['required', Rule::in(['all', 'storefront', 'api'])],
            'deviceFilter' => ['required', Rule::in(['all', 'desktop', 'mobile', 'tablet'])],
        ]);
        [$start, $end] = $this->dates();
        /** @var AnalyticsService $service */
        $service = app(AnalyticsService::class);
        $metrics = $service->getDailyMetrics($this->currentStore(), $start->toDateString(), $end->toDateString());
        $this->totalSales = (int) $metrics->sum('revenue_amount');
        $this->ordersCount = (int) $metrics->sum('orders_count');
        $this->averageOrderValue = $this->ordersCount > 0 ? intdiv($this->totalSales, $this->ordersCount) : 0;
        $this->visitsCount = (int) $metrics->sum('visits_count');
        $this->addToCartCount = (int) $metrics->sum('add_to_cart_count');
        $this->checkoutStartedCount = (int) $metrics->sum('checkout_started_count');
        $completed = (int) $metrics->sum('checkout_completed_count');
        $this->conversionRate = $this->visitsCount > 0 ? round(($completed / $this->visitsCount) * 100, 2) : 0;
        $this->salesChartData = $metrics->map(fn ($metric): array => ['date' => $metric->date->toDateString(), 'revenue' => $metric->revenue_amount, 'orders' => $metric->orders_count])->all();
        $this->loadTopProducts($start, $end);
        $this->loadTopReferrers($start, $end);
        $this->exportUrl = null;
    }

    public function exportCsv(): void
    {
        $this->authorizeAnalytics();
        $this->isExporting = true;
        $rows = ["date,revenue,orders\n"];
        foreach ($this->salesChartData as $row) {
            $rows[] = $row['date'].','.$row['revenue'].','.$row['orders']."\n";
        }
        $this->exportUrl = 'data:text/csv;charset=utf-8,'.rawurlencode(implode('', $rows));
        $this->isExporting = false;
        $this->toast('Analytics export ready');
    }

    public function render(): View
    {
        return $this->admin(view('admin.analytics.index'), 'Analytics', [['label' => 'Analytics']]);
    }

    private function authorizeAnalytics(): void
    {
        $this->requireRoles(['owner', 'admin', 'staff']);
        $this->authorizeAction('view', $this->currentStore());
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dates(): array
    {
        $end = $this->dateRange === 'custom' && $this->customEndDate ? CarbonImmutable::parse($this->customEndDate)->endOfDay() : CarbonImmutable::today()->endOfDay();
        $start = match ($this->dateRange) {
            'today' => $end->startOfDay(),
            '7_days' => $end->subDays(6)->startOfDay(),
            'custom' => $this->customStartDate ? CarbonImmutable::parse($this->customStartDate)->startOfDay() : $end->subDays(29)->startOfDay(),
            default => $end->subDays(29)->startOfDay(),
        };

        return [$start, $end];
    }

    private function loadTopProducts(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $rows = OrderLine::query()
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->currentStore()->id)
            ->whereBetween('orders.placed_at', [$start, $end])
            ->selectRaw('order_lines.product_id, order_lines.title_snapshot as title, SUM(order_lines.quantity) as units, SUM(order_lines.total_amount) as revenue')
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue')->limit(20)->get();
        $total = max(1, (int) $rows->sum('revenue'));
        $this->topProducts = $rows->map(fn ($row): array => [
            'title' => $row->title, 'units' => (int) $row->units, 'revenue' => (int) $row->revenue,
            'percentage' => round(((int) $row->revenue / $total) * 100, 1),
        ])->all();
    }

    private function loadTopReferrers(CarbonImmutable $start, CarbonImmutable $end): void
    {
        /** @var Collection<int, AnalyticsEvent> $events */
        $events = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)
            ->whereBetween('occurred_at', [$start, $end])->get();
        $events = $events->filter(function (AnalyticsEvent $event): bool {
            $channel = (string) data_get($event->properties_json, 'channel', 'storefront');
            $device = (string) data_get($event->properties_json, 'device', 'desktop');

            return ($this->channelFilter === 'all' || $channel === $this->channelFilter)
                && ($this->deviceFilter === 'all' || $device === $this->deviceFilter);
        });
        $this->topReferrers = $events->groupBy(fn (AnalyticsEvent $event): string => (string) data_get($event->properties_json, 'referrer', 'Direct'))
            ->map(function (Collection $sourceEvents, string $source): array {
                $sessions = max(1, $sourceEvents->pluck('session_id')->filter()->unique()->count());
                $orders = $sourceEvents->where('type', 'checkout_completed')->count();

                return ['source' => $source, 'sessions' => $sessions, 'orders' => $orders, 'conversion' => round(($orders / $sessions) * 100, 2)];
            })->sortByDesc('sessions')->take(20)->values()->all();
    }
}
