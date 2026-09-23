<?php

namespace App\Livewire\Admin\Analytics;

use App\Jobs\GenerateAnalyticsExport;
use App\Models\AnalyticsExport;
use App\Services\AnalyticsReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public string $customStartDate = '';

    public string $customEndDate = '';

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public ?int $exportId = null;

    public ?string $exportError = null;

    public function mount(): void
    {
        $today = CarbonImmutable::today('UTC')->toDateString();
        $this->customStartDate = $today;
        $this->customEndDate = $today;
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property, $this->rules());

        if ($this->dateRange === 'custom' && in_array($property, ['customStartDate', 'customEndDate'], true)) {
            $this->validateReportFilters();
        }
    }

    /** @return array<string, mixed> */
    public function loadAnalytics(AnalyticsReportingService $reports): array
    {
        Gate::authorize('view-analytics');
        $this->validateReportFilters();
        [$from, $to] = $this->dateBounds();

        return $reports->report(app('current_store'), $from, $to, $this->channelFilter, $this->deviceFilter);
    }

    public function exportCsv(): void
    {
        Gate::authorize('view-analytics');
        $this->validateReportFilters();
        [$from, $to] = $this->dateBounds();
        $store = app('current_store');
        $export = AnalyticsExport::query()->create([
            'store_id' => $store->getKey(),
            'requested_by_user_id' => auth()->id(),
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'channel' => $this->channelFilter,
            'device' => $this->deviceFilter,
            'status' => 'queued',
        ]);

        $this->exportId = (int) $export->getKey();
        $this->isExporting = true;
        $this->exportUrl = null;
        $this->exportError = null;

        GenerateAnalyticsExport::dispatch($this->exportId)->onConnection('database');
    }

    public function pollExport(): void
    {
        Gate::authorize('view-analytics');

        if ($this->exportId === null) {
            return;
        }

        $export = AnalyticsExport::query()
            ->where('store_id', app('current_store')->getKey())
            ->findOrFail($this->exportId);

        if ($export->status === 'completed' && filled($export->storage_key)) {
            $this->isExporting = false;
            $this->exportUrl = route('admin.analytics.exports.download', ['analyticsExport' => $export->getKey()]);
            $this->exportError = null;

            return;
        }

        if ($export->status === 'failed') {
            $this->isExporting = false;
            $this->exportError = 'The analytics export could not be generated. Try again.';

            return;
        }

        $this->isExporting = true;
    }

    public function render(AnalyticsReportingService $reports): mixed
    {
        return view('livewire.admin.analytics.index', [
            'analytics' => $this->loadAnalytics($reports),
        ])->layout('layouts.admin');
    }

    /** @return array<string, array<int, string|\Illuminate\Validation\Rules\RequiredIf|null>> */
    protected function rules(): array
    {
        return [
            'dateRange' => ['required', Rule::in(['today', 'last_7_days', 'last_30_days', 'custom'])],
            'customStartDate' => ['nullable', 'date_format:Y-m-d'],
            'customEndDate' => ['nullable', 'date_format:Y-m-d'],
            'channelFilter' => ['required', Rule::in(['all', 'storefront', 'api'])],
            'deviceFilter' => ['required', Rule::in(['all', 'desktop', 'mobile', 'tablet'])],
        ];
    }

    private function validateReportFilters(): void
    {
        $this->validate([
            ...$this->rules(),
            'customStartDate' => [Rule::requiredIf($this->dateRange === 'custom'), 'nullable', 'date_format:Y-m-d'],
            'customEndDate' => [Rule::requiredIf($this->dateRange === 'custom'), 'nullable', 'date_format:Y-m-d', 'after_or_equal:customStartDate'],
        ]);

        if ($this->dateRange === 'custom'
            && CarbonImmutable::parse($this->customStartDate)->diffInDays(CarbonImmutable::parse($this->customEndDate)) > 364) {
            throw ValidationException::withMessages(['customEndDate' => 'Choose a date range of 365 days or less.']);
        }
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dateBounds(): array
    {
        $today = CarbonImmutable::today('UTC');

        return match ($this->dateRange) {
            'today' => [$today, $today],
            'last_7_days' => [$today->subDays(6), $today],
            'custom' => [CarbonImmutable::parse($this->customStartDate, 'UTC'), CarbonImmutable::parse($this->customEndDate, 'UTC')],
            default => [$today->subDays(29), $today],
        };
    }
}
