<?php

namespace App\Livewire\Admin;

use App\Enums\FinancialStatus;
use App\Enums\StoreUserRole;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Support\Money;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class Dashboard extends Component
{
    #[Locked]
    public int $storeId;

    public string $storeCurrency = 'EUR';

    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public int $visitorsCount = 0;

    public float $salesChange = 0.0;

    public float $ordersChange = 0.0;

    public float $aovChange = 0.0;

    public float $visitorsChange = 0.0;

    /**
     * @var list<array{date: string, label: string, count: int}>
     */
    public array $ordersChartData = [];

    public int $maxOrdersChartCount = 1;

    /**
     * @var list<array{title: string, units_sold: int, revenue: int}>
     */
    public array $topProducts = [];

    /**
     * @var array{visits: int, add_to_cart: int, checkout_started: int, checkout_completed: int}
     */
    public array $funnelData = [
        'visits' => 0,
        'add_to_cart' => 0,
        'checkout_started' => 0,
        'checkout_completed' => 0,
    ];

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);
        abort_unless($this->canViewAnalytics($store), 403);

        $this->storeId = $store->getKey();
        $this->storeCurrency = $store->default_currency;

        $this->loadKpis();
    }

    public function updatedDateRange(): void
    {
        $this->loadKpis();
    }

    public function updatedCustomStartDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadKpis();
        }
    }

    public function updatedCustomEndDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadKpis();
        }
    }

    public function loadKpis(): void
    {
        [$start, $end] = $this->currentRange();
        [$previousStart, $previousEnd] = $this->previousRange($start, $end);

        $this->ordersCount = $this->ordersBetween($start, $end)->count();
        $previousOrdersCount = $this->ordersBetween($previousStart, $previousEnd)->count();

        $this->totalSales = (int) $this->revenueOrdersBetween($start, $end)->sum('total_amount');
        $previousTotalSales = (int) $this->revenueOrdersBetween($previousStart, $previousEnd)->sum('total_amount');

        $revenueOrdersCount = $this->revenueOrdersBetween($start, $end)->count();
        $previousRevenueOrdersCount = $this->revenueOrdersBetween($previousStart, $previousEnd)->count();

        $this->averageOrderValue = $revenueOrdersCount > 0 ? intdiv($this->totalSales, $revenueOrdersCount) : 0;
        $previousAverageOrderValue = $previousRevenueOrdersCount > 0 ? intdiv($previousTotalSales, $previousRevenueOrdersCount) : 0;

        $this->visitorsCount = 0;
        $this->salesChange = $this->percentChange($this->totalSales, $previousTotalSales);
        $this->ordersChange = $this->percentChange($this->ordersCount, $previousOrdersCount);
        $this->aovChange = $this->percentChange($this->averageOrderValue, $previousAverageOrderValue);
        $this->visitorsChange = 0.0;

        $this->loadChart();
        $this->loadTopProducts();
        $this->loadFunnel();
    }

    public function loadChart(): void
    {
        [$start, $end] = $this->currentRange();

        $counts = $this->ordersBetween($start, $end)
            ->selectRaw('date(placed_at) as order_date, count(*) as aggregate')
            ->groupBy('order_date')
            ->pluck('aggregate', 'order_date');

        $this->ordersChartData = collect(CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay()))
            ->map(fn (CarbonInterface $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->format('M j'),
                'count' => (int) ($counts[$date->toDateString()] ?? 0),
            ])
            ->values()
            ->all();

        $this->maxOrdersChartCount = max(1, max(array_column($this->ordersChartData, 'count') ?: [0]));
    }

    public function loadTopProducts(): void
    {
        [$start, $end] = $this->currentRange();

        $this->topProducts = OrderLine::query()
            ->selectRaw('order_lines.title_snapshot as title, sum(order_lines.quantity) as units_sold, sum(order_lines.total_amount) as revenue')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->storeId)
            ->whereBetween('orders.placed_at', [$start, $end])
            ->whereIn('orders.financial_status', $this->revenueStatuses())
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn (OrderLine $line): array => [
                'title' => (string) $line->title,
                'units_sold' => (int) $line->units_sold,
                'revenue' => (int) $line->revenue,
            ])
            ->all();
    }

    public function loadFunnel(): void
    {
        [$start, $end] = $this->currentRange();

        $this->funnelData = [
            'visits' => 0,
            'add_to_cart' => Cart::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'checkout_started' => Checkout::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'checkout_completed' => $this->ordersCount,
        ];
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
        return view('livewire.admin.dashboard')->layout('layouts.app', [
            'title' => __('Dashboard'),
        ]);
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
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function previousRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $previousEnd = $start->copy()->subSecond();

        return [$start->copy()->subDays($days)->startOfDay(), $previousEnd];
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

    /**
     * @return Builder<Order>
     */
    private function ordersBetween(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return Order::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereBetween('placed_at', [$start, $end]);
    }

    /**
     * @return Builder<Order>
     */
    private function revenueOrdersBetween(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $this->ordersBetween($start, $end)
            ->whereIn('financial_status', $this->revenueStatuses());
    }

    /**
     * @return list<string>
     */
    private function revenueStatuses(): array
    {
        return [
            FinancialStatus::Paid->value,
            FinancialStatus::PartiallyRefunded->value,
        ];
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function canViewAnalytics(Store $store): bool
    {
        $role = auth()->user()?->roleForStoreId($store->getKey());

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true);
    }
}
