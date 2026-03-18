<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public string $period = '30d';

    public string $customStart = '';

    public string $customEnd = '';

    public function mount(): void
    {
        $this->customEnd = now()->toDateString();
        $this->customStart = now()->subDays(30)->toDateString();
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->customEnd = now()->toDateString();
            $this->customStart = match ($this->period) {
                '7d' => now()->subDays(7)->toDateString(),
                '90d' => now()->subDays(90)->toDateString(),
                default => now()->subDays(30)->toDateString(),
            };
        }
    }

    private function getDateRange(): array
    {
        return [$this->customStart, $this->customEnd];
    }

    private function getMetrics(): Collection
    {
        $store = session('current_store_id')
            ? \App\Models\Store::find(session('current_store_id'))
            : null;

        if (! $store) {
            return collect();
        }

        [$start, $end] = $this->getDateRange();

        return app(AnalyticsService::class)->getDailyMetrics($store, $start, $end);
    }

    public function render(): View
    {
        $metrics = $this->getMetrics();

        $totalRevenue = $metrics->sum('revenue_amount');
        $totalOrders = $metrics->sum('orders_count');
        $totalVisits = $metrics->sum('visits_count');
        $totalAddToCart = $metrics->sum('add_to_cart_count');
        $totalCheckoutStarted = $metrics->sum('checkout_started_count');
        $totalCheckoutCompleted = $metrics->sum('checkout_completed_count');

        $aov = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;
        $addToCartRate = $totalVisits > 0 ? round(($totalAddToCart / $totalVisits) * 100, 1) : 0;
        $checkoutConversionRate = $totalCheckoutStarted > 0
            ? round(($totalCheckoutCompleted / $totalCheckoutStarted) * 100, 1)
            : 0;

        $chartLabels = $metrics->pluck('date')->toArray();
        $chartData = $metrics->pluck('revenue_amount')->map(fn ($v) => $v / 100)->toArray();

        return view('livewire.admin.analytics.index', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'aov' => $aov,
            'totalVisits' => $totalVisits,
            'addToCartRate' => $addToCartRate,
            'checkoutConversionRate' => $checkoutConversionRate,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ]);
    }
}
