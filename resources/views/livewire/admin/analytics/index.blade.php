<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <flux:heading size="xl">Analytics</flux:heading>
            <flux:text class="mt-1">Sales, traffic, and conversion signals for the selected store.</flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[760px] xl:grid-cols-5">
            <flux:select wire:model.live="dateRange" aria-label="Date range">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
                <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
                <flux:select.option value="custom">Custom</flux:select.option>
            </flux:select>

            <flux:input type="date" wire:model.live="customStartDate" :disabled="$dateRange !== 'custom'" aria-label="Custom range start" />
            <flux:input type="date" wire:model.live="customEndDate" :disabled="$dateRange !== 'custom'" aria-label="Custom range end" />

            <flux:select wire:model.live="channelFilter" aria-label="Channel">
                <flux:select.option value="all">All channels</flux:select.option>
                <flux:select.option value="storefront">Storefront</flux:select.option>
                <flux:select.option value="api">API</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="deviceFilter" aria-label="Device">
                <flux:select.option value="all">All devices</flux:select.option>
                <flux:select.option value="desktop">Desktop</flux:select.option>
                <flux:select.option value="mobile">Mobile</flux:select.option>
                <flux:select.option value="tablet">Tablet</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <flux:button wire:click="exportCsv" icon="arrow-down-tray" variant="ghost" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
            <span wire:loading wire:target="exportCsv">Exporting...</span>
        </flux:button>

        @if ($exportUrl)
            <flux:button :href="$exportUrl" download="analytics.csv" icon="document-arrow-down" variant="filled">
                Download
            </flux:button>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Revenue', 'value' => $this->formattedTotalSales, 'icon' => 'banknotes'],
            ['label' => 'Orders', 'value' => number_format($ordersCount), 'icon' => 'shopping-bag'],
            ['label' => 'Average order', 'value' => $this->formattedAov, 'icon' => 'receipt-percent'],
            ['label' => 'Conversion', 'value' => number_format($conversionRate, 2).'%', 'icon' => 'chart-bar'],
        ] as $metric)
            <div wire:key="analytics-kpi-{{ $metric['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $metric['value'] }}</div>
                    </div>

                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon :name="$metric['icon']" class="size-5" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Sales over time</flux:heading>
                <flux:text class="mt-1">Daily revenue and order volume</flux:text>
            </div>
        </div>

        <div class="mt-6 flex h-72 items-end gap-1 overflow-hidden rounded-md border border-zinc-100 bg-zinc-50 px-3 pb-3 pt-6 dark:border-zinc-800 dark:bg-zinc-950">
            @foreach ($salesChartData as $point)
                @php
                    $height = $point['revenue'] === 0 ? 2 : max(8, (int) round(($point['revenue'] / $maxSalesChartAmount) * 100));
                @endphp

                <div wire:key="analytics-chart-{{ $point['date'] }}" class="flex min-w-2 flex-1 flex-col items-center justify-end gap-2">
                    <div class="w-full rounded-t bg-sky-500 dark:bg-sky-400" style="height: {{ $height }}%"></div>
                    @if ($loop->first || $loop->last || $loop->iteration % 7 === 0)
                        <div class="hidden text-[11px] text-zinc-500 sm:block">{{ $point['label'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Conversion funnel</flux:heading>
        <flux:text class="mt-1">Traffic progression through checkout</flux:text>

        <div class="mt-5 grid gap-3 md:grid-cols-4">
            @foreach ([
                ['label' => 'Visits', 'value' => $visitsCount],
                ['label' => 'Add to cart', 'value' => $addToCartCount],
                ['label' => 'Checkout started', 'value' => $checkoutStartedCount],
                ['label' => 'Checkout completed', 'value' => $checkoutCompletedCount],
            ] as $step)
                <div wire:key="analytics-funnel-{{ $step['label'] }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $step['label'] }}</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($step['value']) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,0.9fr)]">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Top products</flux:heading>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <tr>
                            <th class="py-3 pr-4">Rank</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-right">Units</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="py-3 pl-4 text-right">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($topProducts as $product)
                            <tr wire:key="analytics-top-product-{{ $product['title'] }}">
                                <td class="py-3 pr-4 font-medium">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">{{ $product['title'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($product['units_sold']) }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\Money::format($product['revenue'], $storeCurrency) }}</td>
                                <td class="py-3 pl-4 text-right">{{ number_format($product['percentage'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-zinc-500">No product sales in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Top referrers</flux:heading>

            <div class="mt-5 space-y-4">
                @forelse ($topReferrers as $referrer)
                    <div wire:key="analytics-referrer-{{ $referrer['source'] }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-4">
                            <div class="font-medium text-zinc-950 dark:text-white">{{ $referrer['source'] }}</div>
                            <flux:badge>{{ number_format($referrer['conversion_rate'], 2) }}%</flux:badge>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                            <div>{{ number_format($referrer['sessions']) }} sessions</div>
                            <div class="text-right">{{ number_format($referrer['orders']) }} orders</div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-10 text-center dark:border-zinc-700">
                        <flux:icon name="cursor-arrow-rays" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-3">No referrer activity in this range.</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
