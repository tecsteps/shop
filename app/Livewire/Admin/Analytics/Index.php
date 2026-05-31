<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Services\AnalyticsService;
use App\Support\Storefront\PriceFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Analytics dashboard backed by the platform AnalyticsService's pre-aggregated
 * daily metrics. KPIs, a daily sales chart, and a conversion funnel for the
 * selected range. Restricted to roles that may view analytics.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;

    #[Url]
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function mount(): void
    {
        if (! Gate::allows('view-analytics')) {
            abort(403);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function bounds(): array
    {
        $end = Carbon::now()->endOfDay();

        return match ($this->dateRange) {
            'today' => [Carbon::now()->startOfDay(), $end],
            'last_7_days' => [Carbon::now()->subDays(6)->startOfDay(), $end],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : $end,
            ],
            default => [Carbon::now()->subDays(29)->startOfDay(), $end],
        };
    }

    /**
     * @return array<string, int>
     */
    public function getSummaryProperty(): array
    {
        [$start, $end] = $this->bounds();

        return app(AnalyticsService::class)->summarize(
            app('current_store'),
            $start->toDateString(),
            $end->toDateString(),
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\AnalyticsDaily>
     */
    public function getDailyProperty()
    {
        [$start, $end] = $this->bounds();

        return app(AnalyticsService::class)->getDailyMetrics(
            app('current_store'),
            $start->toDateString(),
            $end->toDateString(),
        );
    }

    public function getConversionRateProperty(): float
    {
        $summary = $this->summary;
        $visits = $summary['visits_count'] ?? 0;

        if ($visits === 0) {
            return 0.0;
        }

        return round((($summary['checkout_completed_count'] ?? 0) / $visits) * 100, 2);
    }

    public function formattedMoney(int $amount): string
    {
        return PriceFormatter::format($amount, app('current_store')->default_currency);
    }

    public function render()
    {
        return view('livewire.admin.analytics.index');
    }
}
