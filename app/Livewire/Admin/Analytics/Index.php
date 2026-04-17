<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public int $visitsCount = 0;

    public int $addToCartCount = 0;

    public int $checkoutStartedCount = 0;

    public int $checkoutCompletedCount = 0;

    /** @var array<int, array{date: string, revenue: int, orders: int}> */
    public array $chartData = [];

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->loadAnalytics();
    }

    public function updatedCustomStartDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics();
        }
    }

    public function updatedCustomEndDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadAnalytics();
        }
    }

    public function loadAnalytics(): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        if (! $store) {
            return;
        }

        [$startDate, $endDate] = $this->getDateRange();

        $analyticsService = app(AnalyticsService::class);
        $metrics = $analyticsService->getDailyMetrics(
            $store,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $this->totalSales = (int) $metrics->sum('revenue_amount');
        $this->ordersCount = (int) $metrics->sum('orders_count');
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) ($this->totalSales / $this->ordersCount)
            : 0;
        $this->visitsCount = (int) $metrics->sum('visits_count');
        $this->addToCartCount = (int) $metrics->sum('add_to_cart_count');
        $this->checkoutStartedCount = (int) $metrics->sum('checkout_started_count');
        $this->checkoutCompletedCount = (int) $metrics->sum('checkout_completed_count');

        $this->chartData = $metrics->map(fn ($m) => [
            'date' => $m->date,
            'revenue' => $m->revenue_amount,
            'orders' => $m->orders_count,
        ])->values()->toArray();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getDateRange(): array
    {
        $endDate = Carbon::now()->endOfDay();

        return match ($this->dateRange) {
            'today' => [Carbon::today()->startOfDay(), $endDate],
            'last_7_days' => [Carbon::now()->subDays(7)->startOfDay(), $endDate],
            'last_30_days' => [Carbon::now()->subDays(30)->startOfDay(), $endDate],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : $endDate,
            ],
            default => [Carbon::now()->subDays(30)->startOfDay(), $endDate],
        };
    }

    private function formatCurrency(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2);
    }

    public function render()
    {
        $conversionRate = $this->visitsCount > 0
            ? round(($this->checkoutCompletedCount / $this->visitsCount) * 100, 1)
            : 0;

        return view('livewire.admin.analytics.index', [
            'formattedTotalSales' => $this->formatCurrency($this->totalSales),
            'formattedAov' => $this->formatCurrency($this->averageOrderValue),
            'conversionRate' => $conversionRate,
        ])->layout('layouts.admin', ['title' => 'Analytics']);
    }
}
