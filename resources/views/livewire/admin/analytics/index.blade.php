<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Analytics</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Analytics</flux:heading>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:select wire:model.live="dateRange" aria-label="Date range" class="w-44">
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
            <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
            <flux:select.option value="custom">Custom range</flux:select.option>
        </flux:select>
        @if ($this->dateRange === 'custom')
            <flux:input wire:model.live="customStartDate" type="date" aria-label="Start date" />
            <flux:input wire:model.live="customEndDate" type="date" aria-label="End date" />
        @endif
        <flux:select wire:model.live="channelFilter" aria-label="Channel filter" class="w-40">
            <flux:select.option value="all">All channels</flux:select.option>
            <flux:select.option value="storefront">Storefront</flux:select.option>
            <flux:select.option value="api">API</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="deviceFilter" aria-label="Device filter" class="w-40">
            <flux:select.option value="all">All devices</flux:select.option>
            <flux:select.option value="desktop">Desktop</flux:select.option>
            <flux:select.option value="mobile">Mobile</flux:select.option>
            <flux:select.option value="tablet">Tablet</flux:select.option>
        </flux:select>
        <flux:button variant="ghost" wire:click="exportCsv" wire:loading.attr="disabled">
            <flux:icon.arrow-down-tray class="size-4" variant="outline" />
            {{ $this->isExporting ? 'Exporting...' : 'Export CSV' }}
        </flux:button>
        @if ($this->exportUrl !== null)
            <flux:text class="text-sm">Export saved: {{ $this->exportUrl }}</flux:text>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        @php
            $tiles = [
                ['label' => 'Revenue', 'value' => $this->money($summary['revenue_amount'])],
                ['label' => 'Orders', 'value' => number_format($summary['orders_count'])],
                ['label' => 'Avg Order Value', 'value' => $this->money($summary['aov_amount'])],
                ['label' => 'Visits', 'value' => number_format($summary['visits_count'])],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900" wire:key="kpi-{{ $loop->index }}">
                <flux:text variant="subtle">{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
            </div>
        @endforeach
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Sales over time</flux:heading>
        @if (count($daily) === 0)
            <flux:text variant="subtle" class="mt-4">No data for this period.</flux:text>
        @else
            <div class="mt-4 flex h-64 items-end gap-1" role="img" aria-label="Daily revenue chart">
                @php $max = max(1, max(array_column($daily, 'revenue_amount'))); @endphp
                @foreach ($daily as $point)
                    <div
                        class="flex-1 rounded-t bg-zinc-900 dark:bg-white"
                        style="height: {{ max(2, (int) ($point['revenue_amount'] / $max * 100)) }}%"
                        title="{{ $point['date'] }}: {{ $this->money($point['revenue_amount']) }}"
                        wire:key="chart-{{ $point['date'] }}"
                    ></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between">
                <flux:text variant="subtle" class="text-xs">{{ $from }}</flux:text>
                <flux:text variant="subtle" class="text-xs">{{ $to }}</flux:text>
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Conversion funnel</flux:heading>
        @php
            $steps = [
                ['label' => 'Visits', 'value' => $funnelData['visits'], 'class' => 'bg-zinc-300 dark:bg-zinc-600'],
                ['label' => 'Add to Cart', 'value' => $funnelData['add_to_cart'], 'class' => 'bg-zinc-500 dark:bg-zinc-400'],
                ['label' => 'Checkout Started', 'value' => $funnelData['checkout_started'], 'class' => 'bg-zinc-700 dark:bg-zinc-200'],
                ['label' => 'Completed', 'value' => $funnelData['checkout_completed'], 'class' => 'bg-zinc-900 dark:bg-white'],
            ];
            $funnelMax = max(1, $funnelData['visits']);
        @endphp
        <div class="mt-4 flex flex-col gap-3">
            @foreach ($steps as $step)
                <div class="flex items-center gap-3" wire:key="funnel-{{ $loop->index }}">
                    <span class="w-32 shrink-0 text-sm">{{ $step['label'] }}</span>
                    <div class="h-6 flex-1 rounded bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-6 rounded {{ $step['class'] }}" style="width: {{ max(0, (int) ($step['value'] / $funnelMax * 100)) }}%"></div>
                    </div>
                    <span class="w-16 shrink-0 text-right text-sm font-medium">{{ number_format($step['value']) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Top products</flux:heading>
        @if (count($topProducts) === 0)
            <flux:text variant="subtle" class="mt-4">No sales data for this period.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Rank</th>
                            <th class="pb-2 font-medium">Product</th>
                            <th class="pb-2 font-medium">Units Sold</th>
                            <th class="pb-2 font-medium">Revenue</th>
                            <th class="pb-2 font-medium">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="top-{{ $product['product_id'] }}">
                                <td class="py-2">{{ $loop->iteration }}</td>
                                <td class="py-2">{{ $product['title'] }}</td>
                                <td class="py-2">{{ $product['units_sold'] }}</td>
                                <td class="py-2">{{ $this->money($product['revenue_amount']) }}</td>
                                <td class="py-2">{{ $totalRevenue > 0 ? number_format($product['revenue_amount'] / $totalRevenue * 100, 1) : 0 }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Top referrers</flux:heading>
        @if (count($topReferrers) === 0)
            <flux:text variant="subtle" class="mt-4">No traffic data for this period.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Source</th>
                            <th class="pb-2 font-medium">Sessions</th>
                            <th class="pb-2 font-medium">Orders</th>
                            <th class="pb-2 font-medium">Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topReferrers as $referrer)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="referrer-{{ $loop->index }}">
                                <td class="py-2">{{ $referrer['source'] }}</td>
                                <td class="py-2">{{ $referrer['sessions'] }}</td>
                                <td class="py-2">{{ $referrer['orders'] }}</td>
                                <td class="py-2">{{ number_format($referrer['conversion_rate'] * 100, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
