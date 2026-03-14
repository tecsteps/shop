<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $salesChange = 0;

    public float $ordersChange = 0;

    public float $aovChange = 0;

    /** @var array<int, array{order_number: string, email: string, total_amount: int, status: string, placed_at: string}> */
    public array $recentOrders = [];

    public function mount(): void
    {
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
        [$start, $end] = $this->getDateRange();
        $periodLength = $start->diffInDays($end) ?: 1;
        $prevStart = $start->copy()->subDays($periodLength);
        $prevEnd = $start->copy()->subDay();

        $currentOrders = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotNull('placed_at');

        $prevOrders = Order::query()
            ->whereBetween('placed_at', [$prevStart, $prevEnd])
            ->whereNotNull('placed_at');

        $this->totalSales = (int) (clone $currentOrders)->sum('total_amount');
        $this->ordersCount = (clone $currentOrders)->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;

        $prevSales = (int) (clone $prevOrders)->sum('total_amount');
        $prevOrdersCount = (clone $prevOrders)->count();
        $prevAov = $prevOrdersCount > 0 ? (int) round($prevSales / $prevOrdersCount) : 0;

        $this->salesChange = $prevSales > 0
            ? round((($this->totalSales - $prevSales) / $prevSales) * 100, 1)
            : 0;
        $this->ordersChange = $prevOrdersCount > 0
            ? round((($this->ordersCount - $prevOrdersCount) / $prevOrdersCount) * 100, 1)
            : 0;
        $this->aovChange = $prevAov > 0
            ? round((($this->averageOrderValue - $prevAov) / $prevAov) * 100, 1)
            : 0;

        $this->recentOrders = Order::query()
            ->whereNotNull('placed_at')
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'email' => $order->email,
                'total_amount' => $order->total_amount,
                'status' => $order->financial_status?->value ?? $order->status?->value ?? 'unknown',
                'placed_at' => $order->placed_at->diffForHumans(),
            ])
            ->all();
    }

    public function getFormattedTotalSalesProperty(): string
    {
        return '$'.number_format($this->totalSales / 100, 2);
    }

    public function getFormattedAovProperty(): string
    {
        return '$'.number_format($this->averageOrderValue / 100, 2);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today(), Carbon::now()],
            'last_7_days' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()],
            'last_30_days' => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : Carbon::now(),
            ],
            default => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
        };
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.admin', ['title' => 'Dashboard']);
    }
}
