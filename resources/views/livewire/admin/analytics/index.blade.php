<div>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <flux:heading size="xl">Analytics</flux:heading>

        <div class="flex flex-wrap items-center gap-2">
            @if ($dateRange === 'custom')
                <flux:input type="date" wire:model="customStartDate" wire:change="updatedDateRange" class="w-36" />
                <flux:text>to</flux:text>
                <flux:input type="date" wire:model="customEndDate" wire:change="updatedDateRange" class="w-36" />
            @endif

            <flux:dropdown position="bottom" align="end">
                <flux:button variant="outline" icon="calendar-days" icon-trailing="chevron-down">
                    {{ match ($dateRange) {
                        'today' => 'Today',
                        'last_7_days' => 'Last 7 days',
                        'last_30_days' => 'Last 30 days',
                        default => 'Custom range',
                    } }}
                </flux:button>

                <flux:menu>
                    <flux:menu.item wire:click="$set('dateRange', 'today')" :icon="$dateRange === 'today' ? 'check' : null">Today</flux:menu.item>
                    <flux:menu.item wire:click="$set('dateRange', 'last_7_days')" :icon="$dateRange === 'last_7_days' ? 'check' : null">Last 7 days</flux:menu.item>
                    <flux:menu.item wire:click="$set('dateRange', 'last_30_days')" :icon="$dateRange === 'last_30_days' ? 'check' : null">Last 30 days</flux:menu.item>
                    <flux:menu.item wire:click="$set('dateRange', 'custom')" :icon="$dateRange === 'custom' ? 'check' : null">Custom range</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:select wire:model.live="channelFilter" class="w-36">
                <option value="all">All channels</option>
                <option value="storefront">Storefront</option>
                <option value="api">API</option>
            </flux:select>

            <flux:select wire:model.live="deviceFilter" class="w-36">
                <option value="all">All devices</option>
                <option value="desktop">Desktop</option>
                <option value="mobile">Mobile</option>
                <option value="tablet">Tablet</option>
            </flux:select>

            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting...</span>
            </flux:button>
        </div>
    </div>

    {{-- KPI tiles --}}
    <div wire:loading.delay.class="opacity-50" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="p-5">
            <flux:text>Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedTotalSales }}</flux:heading>
        </flux:card>
        <flux:card class="p-5">
            <flux:text>Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
        </flux:card>
        <flux:card class="p-5">
            <flux:text>Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedAov }}</flux:heading>
        </flux:card>
        <flux:card class="p-5">
            <flux:text>Conversion Rate</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $conversionRate }}%</flux:heading>
        </flux:card>
    </div>

    {{-- Sales chart --}}
    <flux:card class="mt-6 p-6">
        <flux:heading size="lg">Sales over time</flux:heading>

        @php
            $maxRevenue = max(1, collect($salesChartData)->max('revenue'));
        @endphp

        <div class="mt-6 flex h-72 items-end gap-1">
            @forelse ($salesChartData as $point)
                <div class="group relative flex-1">
                    <div
                        class="w-full rounded-t bg-zinc-300 transition-colors group-hover:bg-zinc-500 dark:bg-zinc-700 dark:group-hover:bg-zinc-500"
                        style="height: {{ max(2, round($point['revenue'] / $maxRevenue * 100)) }}%"
                    ></div>
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-zinc-900 px-2 py-1 text-xs text-white group-hover:block dark:bg-white dark:text-zinc-900">
                        {{ $point['date'] }} — {{ $this->formatMoney($point['revenue']) }} ({{ $point['count'] }} orders)
                    </div>
                </div>
            @empty
                <div class="flex w-full items-center justify-center text-sm text-zinc-400">
                    No sales data for this period.
                </div>
            @endforelse
        </div>
    </flux:card>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top products --}}
        <flux:card class="p-6">
            <flux:heading size="lg">Top products</flux:heading>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                            <th class="py-2 pe-3 text-start font-medium">Rank</th>
                            <th class="py-2 pe-3 text-start font-medium">Product</th>
                            <th class="py-2 pe-3 text-end font-medium">Units Sold</th>
                            <th class="py-2 pe-3 text-end font-medium">Revenue</th>
                            <th class="py-2 text-end font-medium">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $row)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                <td class="py-2.5 pe-3 text-zinc-400">{{ $row['rank'] }}</td>
                                <td class="py-2.5 pe-3 font-medium text-zinc-800 dark:text-white">{{ $row['title'] }}</td>
                                <td class="py-2.5 pe-3 text-end text-zinc-500 dark:text-zinc-300">{{ $row['units_sold'] }}</td>
                                <td class="py-2.5 pe-3 text-end text-zinc-500 dark:text-zinc-300">{{ $this->formatMoney($row['revenue']) }}</td>
                                <td class="py-2.5 text-end text-zinc-500 dark:text-zinc-300">{{ $row['percentage'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400">No sales data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- Top referrers --}}
        <flux:card class="p-6">
            <flux:heading size="lg">Top referrers</flux:heading>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                            <th class="py-2 pe-3 text-start font-medium">Source</th>
                            <th class="py-2 pe-3 text-end font-medium">Sessions</th>
                            <th class="py-2 pe-3 text-end font-medium">Orders</th>
                            <th class="py-2 text-end font-medium">Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topReferrers as $row)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                <td class="py-2.5 pe-3 font-medium text-zinc-800 dark:text-white">{{ $row['source'] }}</td>
                                <td class="py-2.5 pe-3 text-end text-zinc-500 dark:text-zinc-300">{{ number_format($row['sessions']) }}</td>
                                <td class="py-2.5 pe-3 text-end text-zinc-500 dark:text-zinc-300">{{ $row['orders'] }}</td>
                                <td class="py-2.5 text-end text-zinc-500 dark:text-zinc-300">{{ $row['conversion_rate'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-400">No traffic data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>
</div>
