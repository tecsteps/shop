@php
    /** @var \App\Support\Money $money */
    $money = \App\Support\Money::class;

    $tiles = [
        ['label' => 'Total Sales', 'value' => $money::format($totalSales, $currency)],
        ['label' => 'Orders', 'value' => number_format($ordersCount)],
        ['label' => 'Avg. Order Value', 'value' => $money::format($averageOrderValue, $currency)],
        ['label' => 'Conversion Rate', 'value' => $conversionRate === null ? '—' : $conversionRate.'%'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Analytics</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-40" aria-label="Date range">
            <flux:select.option value="7">Last 7 days</flux:select.option>
            <flux:select.option value="30">Last 30 days</flux:select.option>
            <flux:select.option value="90">Last 90 days</flux:select.option>
        </flux:select>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50" wire:target="dateRange">
        @foreach ($tiles as $tile)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
            </div>
        @endforeach
    </div>

    {{-- Sales chart --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Sales over time</flux:heading>

        <div class="relative mt-4" wire:loading.class="opacity-50" wire:target="dateRange">
            @if (array_sum(array_column($salesChart['days'], 'value')) > 0)
                <svg viewBox="0 0 600 160" class="h-48 w-full" role="img" aria-label="Daily revenue for the selected period" preserveAspectRatio="none">
                    <line x1="0" y1="150" x2="600" y2="150" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                    <polyline
                        fill="none"
                        class="stroke-blue-500"
                        stroke-width="2"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                        points="{{ $salesChart['points'] }}" />
                </svg>
                <div class="mt-2 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span>{{ $salesChart['days'][0]['date'] ?? '' }}</span>
                    <span>{{ $salesChart['days'][array_key_last($salesChart['days'])]['date'] ?? '' }}</span>
                </div>
            @else
                <p class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No sales in this period.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Traffic chart --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Visits over time</flux:heading>

            <div class="relative mt-4" wire:loading.class="opacity-50" wire:target="dateRange">
                @if (array_sum(array_column($trafficChart['days'], 'value')) > 0)
                    <svg viewBox="0 0 600 160" class="h-40 w-full" role="img" aria-label="Daily visits for the selected period" preserveAspectRatio="none">
                        <line x1="0" y1="150" x2="600" y2="150" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                        <polyline
                            fill="none"
                            class="stroke-emerald-500"
                            stroke-width="2"
                            stroke-linejoin="round"
                            stroke-linecap="round"
                            points="{{ $trafficChart['points'] }}" />
                    </svg>
                    <div class="mt-2 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $trafficChart['days'][0]['date'] ?? '' }}</span>
                        <span>{{ $trafficChart['days'][array_key_last($trafficChart['days'])]['date'] ?? '' }}</span>
                    </div>
                @else
                    <p class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No visits in this period.</p>
                @endif
            </div>
        </div>

        {{-- Conversion funnel --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Conversion funnel</flux:heading>

            <div class="mt-4 space-y-4" wire:loading.class="opacity-50" wire:target="dateRange">
                @foreach ($funnel as $step)
                    <div>
                        <div class="flex items-baseline justify-between gap-4">
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">{{ $step['label'] }}</flux:text>
                            <flux:text class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($step['count']) }}
                                @if ($step['percent'] !== null)
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $step['percent'] }}%)</span>
                                @endif
                            </flux:text>
                        </div>
                        <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                             role="img" aria-label="{{ $step['label'] }}: {{ $step['count'] }}">
                            <div class="h-full rounded-full bg-blue-500" style="width: {{ $step['width'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top products --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Top products</flux:heading>

        @if ($topProducts->isEmpty())
            <p class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No product sales in this period.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4 font-medium">Rank</th>
                            <th class="py-2 pr-4 font-medium">Product</th>
                            <th class="py-2 pr-4 text-right font-medium">Units Sold</th>
                            <th class="py-2 pr-4 text-right font-medium">Revenue</th>
                            <th class="py-2 text-right font-medium">% of Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($topProducts as $product)
                            <tr>
                                <td class="py-2.5 pr-4 text-zinc-500 dark:text-zinc-400">{{ $loop->iteration }}</td>
                                <td class="py-2.5 pr-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $product->title }}</td>
                                <td class="py-2.5 pr-4 text-right text-zinc-600 dark:text-zinc-300">{{ number_format((int) $product->units) }}</td>
                                <td class="py-2.5 pr-4 text-right text-zinc-900 dark:text-zinc-100">{{ $money::format((int) $product->revenue, $currency) }}</td>
                                <td class="py-2.5 text-right text-zinc-600 dark:text-zinc-300">
                                    {{ $topProductsRevenue > 0 ? round($product->revenue / $topProductsRevenue * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent search queries --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Recent search queries</flux:heading>

        @if ($recentSearches->isEmpty())
            <p class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No search queries yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4 font-medium">Query</th>
                            <th class="py-2 pr-4 text-right font-medium">Results</th>
                            <th class="py-2 text-right font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentSearches as $search)
                            <tr>
                                <td class="py-2.5 pr-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $search->query }}</td>
                                <td class="py-2.5 pr-4 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($search->results_count) }}</td>
                                <td class="py-2.5 text-right text-zinc-600 dark:text-zinc-300">{{ $search->created_at?->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
