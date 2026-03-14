<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
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

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        [$startDate, $endDate] = $this->getDateRange();

        $query = Order::query()
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [$startDate, $endDate]);

        $this->totalSales = (int) $query->sum('total_amount');
        $this->ordersCount = $query->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) ($this->totalSales / $this->ordersCount)
            : 0;
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
        return view('livewire.admin.analytics.index', [
            'formattedTotalSales' => $this->formatCurrency($this->totalSales),
            'formattedAov' => $this->formatCurrency($this->averageOrderValue),
        ])->layout('layouts.admin', ['title' => 'Analytics']);
    }
}
