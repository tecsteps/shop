<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Analytics')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Analytics') }}</flux:heading>

        <div class="flex flex-wrap items-center gap-2">
            <flux:select wire:model.live="dateRange" size="sm" class="max-w-44" data-test="analytics-date-range">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="last_7_days">{{ __('Last 7 days') }}</flux:select.option>
                <flux:select.option value="last_30_days">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom range') }}</flux:select.option>
            </flux:select>

            @if ($dateRange === 'custom')
                <flux:input wire:model.live="customStartDate" type="date" size="sm" data-test="analytics-start-date" />
                <flux:input wire:model.live="customEndDate" type="date" size="sm" data-test="analytics-end-date" />
            @endif
        </div>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        @foreach ([
            ['label' => __('Total sales'), 'value' => $formattedTotalSales, 'change' => $salesChange, 'test' => 'analytics-kpi-total-sales'],
            ['label' => __('Orders'), 'value' => number_format($ordersCount), 'change' => $ordersChange, 'test' => 'analytics-kpi-orders'],
            ['label' => __('Average order value'), 'value' => $formattedAov, 'change' => $aovChange, 'test' => 'analytics-kpi-aov'],
            ['label' => __('Conversion rate'), 'value' => number_format($conversionRate, 1).'%', 'change' => $conversionChange, 'test' => 'analytics-kpi-conversion'],
        ] as $tile)
            <x-admin.card data-test="{{ $tile['test'] }}">
                <flux:text>{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
                <div class="mt-2 flex items-center gap-1.5">
                    <flux:badge size="sm" :color="$tile['change'] >= 0 ? 'green' : 'red'">
                        {{ ($tile['change'] >= 0 ? '+' : '').number_format($tile['change'], 1) }}%
                    </flux:badge>
                    <flux:icon
                        :name="$tile['change'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'"
                        variant="micro"
                        class="{{ $tile['change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                    />
                    <flux:text class="text-xs">{{ __('vs previous period') }}</flux:text>
                </div>
            </x-admin.card>
        @endforeach
    </div>

    {{-- Sales over time (inline SVG line chart, no JS chart dependency) --}}
    <x-admin.card>
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Sales over time') }}</flux:heading>
            <flux:text class="text-xs">
                {{ __('Peak: :max/day', ['max' => \App\Support\Storefront\PriceFormatter::format($chart['max'], app('current_store')->default_currency ?? 'EUR')]) }}
            </flux:text>
        </div>

        <div class="mt-4" wire:loading.class="opacity-50">
            <svg viewBox="0 0 600 180" preserveAspectRatio="none" class="h-52 w-full" role="img" aria-label="{{ __('Daily revenue') }}">
                <line x1="0" y1="175" x2="600" y2="175" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                <line x1="0" y1="90" x2="600" y2="90" class="stroke-zinc-100 dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="4 4" />
                <polygon points="{{ $chart['area'] }}" class="fill-blue-500/10 dark:fill-blue-400/10" />
                <polyline
                    points="{{ $chart['points'] }}"
                    fill="none"
                    class="stroke-blue-600 dark:stroke-blue-400"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                    vector-effect="non-scaling-stroke"
                />
            </svg>
            <div class="mt-1 flex justify-between text-xs text-zinc-400 dark:text-zinc-500">
                <span>{{ \Illuminate\Support\Carbon::parse($chart['days'][0]['date'])->format('M j') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse(end($chart['days'])['date'])->format('M j') }}</span>
            </div>
        </div>
    </x-admin.card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Conversion funnel --}}
        <x-admin.card data-test="analytics-funnel">
            <flux:heading size="lg">{{ __('Conversion funnel') }}</flux:heading>

            <div class="mt-5 space-y-4">
                @foreach ($funnel as $index => $step)
                    <div wire:key="funnel-step-{{ $index }}">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $step['label'] }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ number_format($step['count']) }}</span>
                        </div>
                        <div class="mt-1.5 h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div
                                class="h-full rounded-full {{ ['bg-blue-300 dark:bg-blue-900', 'bg-blue-400 dark:bg-blue-800', 'bg-blue-500 dark:bg-blue-700', 'bg-blue-600 dark:bg-blue-600', 'bg-blue-700 dark:bg-blue-500'][$index] }}"
                                style="width: {{ max($step['count'] > 0 ? 2 : 0, $step['percent']) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:text class="mt-4 text-xs">
                {{ trans_choice(':count unique visit in this period|:count unique visits in this period', $visitsCount, ['count' => number_format($visitsCount)]) }}
            </flux:text>
        </x-admin.card>

        {{-- Top referrers --}}
        <x-admin.card class="!p-0" data-test="analytics-referrers">
            <div class="p-6 pb-4">
                <flux:heading size="lg">{{ __('Top referrers') }}</flux:heading>
            </div>

            @if ($topReferrers === [])
                <div class="px-6 pb-6">
                    <flux:text>{{ __('No traffic data for this period.') }}</flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                <th class="px-6 py-2.5">{{ __('Source') }}</th>
                                <th class="px-6 py-2.5 text-right">{{ __('Sessions') }}</th>
                                <th class="px-6 py-2.5 text-right">{{ __('Orders') }}</th>
                                <th class="px-6 py-2.5 text-right">{{ __('Conversion') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($topReferrers as $referrer)
                                <tr wire:key="referrer-{{ $loop->index }}">
                                    <td class="px-6 py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $referrer['source'] }}</td>
                                    <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($referrer['sessions']) }}</td>
                                    <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($referrer['orders']) }}</td>
                                    <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($referrer['conversion'], 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.card>
    </div>

    {{-- Top products --}}
    <x-admin.card class="!p-0" data-test="analytics-top-products">
        <div class="p-6 pb-4">
            <flux:heading size="lg">{{ __('Top products') }}</flux:heading>
        </div>

        @if ($topProducts === [])
            <div class="px-6 pb-6">
                <flux:text>{{ __('No sales data for this period.') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-6 py-2.5">{{ __('Rank') }}</th>
                            <th class="px-6 py-2.5">{{ __('Product') }}</th>
                            <th class="px-6 py-2.5 text-right">{{ __('Units sold') }}</th>
                            <th class="px-6 py-2.5 text-right">{{ __('Revenue') }}</th>
                            <th class="px-6 py-2.5 text-right">{{ __('% of total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($topProducts as $product)
                            <tr wire:key="top-product-{{ $loop->index }}">
                                <td class="px-6 py-3 text-zinc-500 dark:text-zinc-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $product['title'] }}</td>
                                <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($product['units_sold']) }}</td>
                                <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                    {{ \App\Support\Storefront\PriceFormatter::format($product['revenue'], app('current_store')->default_currency ?? 'EUR') }}
                                </td>
                                <td class="px-6 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($product['share'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</div>
