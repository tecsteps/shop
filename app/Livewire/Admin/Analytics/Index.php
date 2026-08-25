<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderLine;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts, FormatsMoney;

    #[Layout('layouts.admin.app')]
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $conversionRate = 0.0;

    /** @var list<array{date: string, revenue: int, count: int}> */
    public array $salesChartData = [];

    /** @var list<array{rank: int, title: string, units_sold: int, revenue: int, percentage: float}> */
    public array $topProducts = [];

    /** @var list<array{source: string, sessions: int, orders: int, conversion_rate: float}> */
    public array $topReferrers = [];

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);

        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->loadAnalytics();
    }

    public function updatedChannelFilter(): void
    {
        $this->loadAnalytics();
    }

    public function updatedDeviceFilter(): void
    {
        $this->loadAnalytics();
    }

    #[Computed]
    public function formattedTotalSales(): string
    {
        return $this->formatMoney($this->totalSales);
    }

    #[Computed]
    public function formattedAov(): string
    {
        return $this->formatMoney($this->averageOrderValue);
    }

    public function loadAnalytics(): void
    {
        ['start' => $start, 'end' => $end] = $this->range();

        $orders = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->get();

        $this->ordersCount = $orders->count();
        $this->totalSales = (int) $orders->sum('total_amount');
        $this->averageOrderValue = $this->ordersCount > 0 ? intdiv($this->totalSales, $this->ordersCount) : 0;

        $daily = AnalyticsDaily::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $visits = (int) $daily->sum('visits_count');
        $this->conversionRate = $visits > 0 ? round($this->ordersCount / $visits * 100, 2) : 0.0;

        $this->loadChart($start, $end);
        $this->loadTopProducts($start, $end);
        $this->loadTopReferrers($start, $end);
    }

    public function exportCsv()
    {
        $this->authorize('viewAny', Order::class);

        ['start' => $start, 'end' => $end] = $this->range();

        $orders = Order::query()
            ->with('customer')
            ->whereBetween('placed_at', [$start, $end])
            ->get();

        $filename = 'orders-'.$start->toDateString().'-'.$end->toDateString().'.csv';

        $this->isExporting = true;

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Order #', 'Date', 'Customer', 'Email', 'Total', 'Status']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->placed_at?->toDateTimeString(),
                    $order->customer?->name ?? 'Guest',
                    $order->email,
                    number_format($order->total_amount / 100, 2),
                    $order->status,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function range(): array
    {
        if ($this->dateRange === 'custom' && $this->customStartDate && $this->customEndDate) {
            return [
                'start' => CarbonImmutable::parse($this->customStartDate)->startOfDay(),
                'end' => CarbonImmutable::parse($this->customEndDate)->endOfDay(),
            ];
        }

        $end = CarbonImmutable::now()->endOfDay();

        $start = match ($this->dateRange) {
            'today' => CarbonImmutable::today()->startOfDay(),
            'last_7_days' => $end->subDays(6)->startOfDay(),
            default => $end->subDays(29)->startOfDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function loadChart(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $rows = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('DATE(placed_at) as date, SUM(total_amount) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $data = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $data[] = [
                'date' => $key,
                'revenue' => (int) ($row->revenue ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];

            $cursor = $cursor->addDay();
        }

        $this->salesChartData = $data;
    }

    private function loadTopProducts(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $rows = OrderLine::query()
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->join('products', 'products.id', '=', 'order_lines.product_id')
            ->whereBetween('orders.placed_at', [$start, $end])
            ->selectRaw('products.title as title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();

        $total = max(1, (int) $rows->sum('revenue'));

        $this->topProducts = $rows->values()->map(fn ($row, $index) => [
            'rank' => $index + 1,
            'title' => $row->title,
            'units_sold' => (int) $row->units_sold,
            'revenue' => (int) $row->revenue,
            'percentage' => round((int) $row->revenue / $total * 100, 1),
        ])->all();
    }

    private function loadTopReferrers(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $events = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->get();

        $referrers = [];

        foreach ($events as $event) {
            $props = $event->properties_json ?? [];
            $referrer = (string) ($props['referrer'] ?? 'Direct');

            if (! isset($referrers[$referrer])) {
                $referrers[$referrer] = ['sessions' => [], 'orders' => 0];
            }

            if ($event->type === 'page_view' && $event->session_id) {
                $referrers[$referrer]['sessions'][$event->session_id] = true;
            }

            if ($event->type === 'checkout_completed') {
                $referrers[$referrer]['orders']++;
            }
        }

        $this->topReferrers = collect($referrers)
            ->map(fn ($data, $source) => [
                'source' => $source,
                'sessions' => count($data['sessions']),
                'orders' => $data['orders'],
                'conversion_rate' => count($data['sessions']) > 0
                    ? round($data['orders'] / count($data['sessions']) * 100, 2)
                    : 0.0,
            ])
            ->sortByDesc('sessions')
            ->take(10)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.analytics.index');
    }
}
