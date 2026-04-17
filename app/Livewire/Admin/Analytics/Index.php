<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->endDate = now()->toDateString();
        $this->startDate = now()->subDays(29)->toDateString();
    }

    public function render(AnalyticsService $analytics): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $metrics = $analytics->getDailyMetrics($store, $this->startDate, $this->endDate);

        $totals = [
            'revenue' => (int) $metrics->sum('revenue_amount'),
            'orders' => (int) $metrics->sum('orders_count'),
            'visits' => (int) $metrics->sum('visits_count'),
            'checkouts_started' => (int) $metrics->sum('checkout_started_count'),
            'checkouts_completed' => (int) $metrics->sum('checkout_completed_count'),
        ];

        $totals['aov'] = $totals['orders'] > 0
            ? (int) round($totals['revenue'] / $totals['orders'])
            : 0;

        return view('livewire.admin.analytics.index', [
            'metrics' => $metrics,
            'totals' => $totals,
            'currency' => $store->default_currency ?? 'EUR',
        ]);
    }
}
