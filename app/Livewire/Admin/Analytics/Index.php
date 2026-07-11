<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\AdminComponent;
use App\Models\AnalyticsDaily;
use Carbon\CarbonImmutable;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    public string $dateRange = 'last_30_days';

    public int $revenue = 0;

    public int $orders = 0;

    public int $visits = 0;

    public float $conversionRate = 0;

    public array $daily = [];

    public function mount(): void
    {
        $this->authorizeStore();
        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        $days = $this->dateRange === 'last_7_days' ? 7 : 30;
        $rows = AnalyticsDaily::query()->where('store_id', $this->currentStore()->getKey())->where('date', '>=', CarbonImmutable::today()->subDays($days - 1))->orderBy('date')->get();
        $this->revenue = (int) $rows->sum('revenue_amount');
        $this->orders = (int) $rows->sum('orders_count');
        $this->visits = (int) $rows->sum('visits_count');
        $completed = (int) $rows->sum('checkout_completed_count');
        $this->conversionRate = $this->visits > 0 ? round($completed / $this->visits * 100, 2) : 0;
        $this->daily = $rows->map(fn (AnalyticsDaily $row): array => ['date' => $row->date->toDateString(), 'revenue' => $row->revenue_amount, 'orders' => $row->orders_count, 'visits' => $row->visits_count])->all();
    }

    public function formattedRevenue(): string
    {
        return $this->currency($this->revenue);
    }

    public function render()
    {
        return view('livewire.admin.analytics.index');
    }
}
