<div>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>

        <div class="flex items-center gap-2">
            @if ($dateRange === 'custom')
                <div class="flex items-center gap-2">
                    <flux:input type="date" wire:model="customStartDate" wire:change="updatedDateRange" class="w-40" />
                    <flux:text>to</flux:text>
                    <flux:input type="date" wire:model="customEndDate" wire:change="updatedDateRange" class="w-40" />
                </div>
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
        </div>
    </div>

    {{-- KPI tiles --}}
    <div wire:loading.delay.class="opacity-50" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="p-5">
            <flux:text>Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedTotalSales }}</flux:heading>
            <div class="mt-3">
                <flux:badge :color="$salesChange >= 0 ? 'green' : 'red'" :icon="$salesChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="gap-1">
                    {{ $salesChange >= 0 ? '+' : '' }}{{ $salesChange }}%
                </flux:badge>
            </div>
        </flux:card>

        <flux:card class="p-5">
            <flux:text>Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
            <div class="mt-3">
                <flux:badge :color="$ordersChange >= 0 ? 'green' : 'red'" :icon="$ordersChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="gap-1">
                    {{ $ordersChange >= 0 ? '+' : '' }}{{ $ordersChange }}%
                </flux:badge>
            </div>
        </flux:card>

        <flux:card class="p-5">
            <flux:text>Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedAov }}</flux:heading>
            <div class="mt-3">
                <flux:badge :color="$aovChange >= 0 ? 'green' : 'red'" :icon="$aovChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="gap-1">
                    {{ $aovChange >= 0 ? '+' : '' }}{{ $aovChange }}%
                </flux:badge>
            </div>
        </flux:card>

        <flux:card class="p-5">
            <flux:text>Visitors</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($visitorsCount) }}</flux:heading>
            <div class="mt-3">
                <flux:badge :color="$visitorsChange >= 0 ? 'green' : 'red'" :icon="$visitorsChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="gap-1">
                    {{ $visitorsChange >= 0 ? '+' : '' }}{{ $visitorsChange }}%
                </flux:badge>
            </div>
        </flux:card>
    </div>

    {{-- Orders chart --}}
    <flux:card class="mt-6 p-6">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Orders over time</flux:heading>
            <div wire:loading class="text-sm text-zinc-400">
                <flux:icon.loading class="size-4 animate-spin" />
            </div>
        </div>

        @php
            $maxCount = max(1, collect($ordersChartData)->max('count'));
        @endphp

        <div class="mt-6 flex h-64 items-end gap-1">
            @forelse ($ordersChartData as $point)
                <div class="group relative flex-1">
                    <div
                        class="w-full rounded-t bg-zinc-300 transition-colors group-hover:bg-zinc-500 dark:bg-zinc-700 dark:group-hover:bg-zinc-500"
                        style="height: {{ max(2, round($point['count'] / $maxCount * 100)) }}%"
                    ></div>
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-zinc-900 px-2 py-1 text-xs text-white group-hover:block dark:bg-white dark:text-zinc-900">
                        {{ $point['date'] }} — {{ $point['count'] }} orders
                    </div>
                </div>
            @empty
                <div class="flex w-full items-center justify-center text-sm text-zinc-400">
                    No order data for this period.
                </div>
            @endforelse
        </div>
    </flux:card>

    {{-- Top products + funnel --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="p-6">
            <flux:heading size="lg">Top products</flux:heading>

            @if ($topProducts === [])
                <flux:text class="mt-4">No sales data for this period.</flux:text>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                <th class="py-2 pe-3 text-start font-medium">Product Title</th>
                                <th class="py-2 pe-3 text-end font-medium">Units Sold</th>
                                <th class="py-2 text-end font-medium">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $row)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="py-2.5 pe-3 font-medium text-zinc-800 dark:text-white">{{ $row['title'] }}</td>
                                    <td class="py-2.5 pe-3 text-end text-zinc-500 dark:text-zinc-300">{{ $row['units_sold'] }}</td>
                                    <td class="py-2.5 text-end text-zinc-500 dark:text-zinc-300">{{ $this->formatMoney($row['revenue']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>

        <flux:card class="p-6">
            <flux:heading size="lg">Conversion funnel</flux:heading>

            @php
                $funnelSteps = [
                    ['label' => 'Visits', 'key' => 'visits'],
                    ['label' => 'Add to Cart', 'key' => 'add_to_cart'],
                    ['label' => 'Checkout Started', 'key' => 'checkout_started'],
                    ['label' => 'Checkout Completed', 'key' => 'checkout_completed'],
                ];
                $funnelMax = max(1, ...array_values($funnelData));
                $barColors = ['bg-zinc-300 dark:bg-zinc-600', 'bg-zinc-500 dark:bg-zinc-500', 'bg-zinc-700 dark:bg-zinc-400', 'bg-zinc-900 dark:bg-zinc-300'];
            @endphp

            <div class="mt-6 space-y-4">
                @foreach ($funnelSteps as $index => $step)
                    <div class="flex items-center gap-3">
                        <span class="w-32 shrink-0 text-sm text-zinc-600 dark:text-zinc-300">{{ $step['label'] }}</span>
                        <div class="h-4 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                            <div
                                class="h-full rounded transition-all {{ $barColors[$index] }}"
                                style="width: {{ round($funnelData[$step['key']] / $funnelMax * 100) }}%"
                            ></div>
                        </div>
                        <span class="w-16 shrink-0 text-end text-sm font-medium text-zinc-800 dark:text-white">
                            {{ number_format($funnelData[$step['key']]) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>
