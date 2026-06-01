@php
    $summary = $this->summary;
    $daily = $this->daily;
    $maxRevenue = max(1, $daily->max('revenue_amount') ?? 1);
    $funnel = [
        ['label' => __('Visits'), 'value' => $summary['visits_count'] ?? 0],
        ['label' => __('Add to Cart'), 'value' => $summary['add_to_cart_count'] ?? 0],
        ['label' => __('Checkout Started'), 'value' => $summary['checkout_started_count'] ?? 0],
        ['label' => __('Checkout Completed'), 'value' => $summary['checkout_completed_count'] ?? 0],
    ];
    $funnelMax = max(1, $funnel[0]['value']);
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Analytics')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Analytics') }}</flux:heading>
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="dateRange" size="sm" class="w-44">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="last_7_days">{{ __('Last 7 days') }}</flux:select.option>
                <flux:select.option value="last_30_days">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom range') }}</flux:select.option>
            </flux:select>
            @if ($dateRange === 'custom')
                <flux:input type="date" wire:model.live="customStartDate" size="sm" />
                <flux:input type="date" wire:model.live="customEndDate" size="sm" />
            @endif
        </div>
    </div>

    {{-- KPI tiles. --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.card>
            <flux:text class="text-sm">{{ __('Total Sales') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedMoney($summary['revenue_amount'] ?? 0) }}</flux:heading>
        </x-admin.card>
        <x-admin.card>
            <flux:text class="text-sm">{{ __('Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($summary['orders_count'] ?? 0) }}</flux:heading>
        </x-admin.card>
        <x-admin.card>
            <flux:text class="text-sm">{{ __('Avg Order Value') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedMoney($summary['aov_amount'] ?? 0) }}</flux:heading>
        </x-admin.card>
        <x-admin.card>
            <flux:text class="text-sm">{{ __('Conversion Rate') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($this->conversionRate, 2) }}%</flux:heading>
        </x-admin.card>
    </div>

    {{-- Sales chart. --}}
    <x-admin.card class="mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('Revenue over time') }}</flux:heading>
        @if ($daily->isEmpty())
            <flux:text>{{ __('No analytics data for this period.') }}</flux:text>
        @else
            <div class="flex h-48 items-end gap-1">
                @foreach ($daily as $day)
                    <div class="group relative flex flex-1 flex-col items-center justify-end">
                        <div class="w-full rounded-t bg-emerald-500/80 transition-all hover:bg-emerald-600" style="height: {{ max(2, (int) round(($day->revenue_amount / $maxRevenue) * 100)) }}%"></div>
                        <span class="pointer-events-none absolute -top-7 hidden whitespace-nowrap rounded bg-zinc-900 px-2 py-1 text-xs text-white group-hover:block">
                            {{ \Illuminate\Support\Carbon::parse($day->date)->format('M j') }}: {{ $this->formattedMoney($day->revenue_amount) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.card>

    {{-- Conversion funnel. --}}
    <x-admin.card class="mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('Conversion funnel') }}</flux:heading>
        <div class="space-y-3">
            @foreach ($funnel as $i => $step)
                <div class="flex items-center gap-4">
                    <span class="w-40 text-sm text-zinc-600 dark:text-zinc-400">{{ $step['label'] }}</span>
                    <div class="flex-1">
                        <div class="h-6 rounded {{ ['bg-blue-600', 'bg-blue-500', 'bg-blue-400', 'bg-blue-300'][$i] }}" style="width: {{ max(2, (int) round(($step['value'] / $funnelMax) * 100)) }}%"></div>
                    </div>
                    <span class="w-16 text-right text-sm font-medium">{{ number_format($step['value']) }}</span>
                </div>
            @endforeach
        </div>
    </x-admin.card>
</div>
