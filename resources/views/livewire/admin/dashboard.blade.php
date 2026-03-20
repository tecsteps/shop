<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:select wire:model.live="dateRange" class="w-44">
            <option value="today">Today</option>
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
            <option value="custom">Custom range</option>
        </flux:select>
    </div>

    @if($dateRange === 'custom')
        <div class="mt-4 flex gap-4">
            <flux:input wire:model.live.debounce.500ms="customStartDate" type="date" label="Start date" />
            <flux:input wire:model.live.debounce.500ms="customEndDate" type="date" label="End date" />
        </div>
    @endif

    {{-- KPI tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tiles = [
                ['label' => 'Total Sales', 'value' => '$' . number_format($kpis['totalSales'] / 100, 2), 'change' => $kpis['salesChange']],
                ['label' => 'Orders', 'value' => number_format($kpis['ordersCount']), 'change' => $kpis['ordersChange']],
                ['label' => 'Avg Order Value', 'value' => '$' . number_format($kpis['aov'] / 100, 2), 'change' => $kpis['aovChange']],
                ['label' => 'Visitors', 'value' => number_format($kpis['visitorsCount']), 'change' => $kpis['visitorsChange']],
            ];
        @endphp

        @foreach($tiles as $tile)
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tile['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $tile['value'] }}</p>
                <div class="mt-1">
                    @if($tile['change'] > 0)
                        <flux:badge color="green" size="sm">+{{ $tile['change'] }}%</flux:badge>
                    @elseif($tile['change'] < 0)
                        <flux:badge color="red" size="sm">{{ $tile['change'] }}%</flux:badge>
                    @else
                        <flux:badge size="sm">0%</flux:badge>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Orders chart --}}
    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <flux:heading size="lg">Orders over time</flux:heading>
        @if(empty($chartData))
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No data for this period.</p>
        @else
            <div class="mt-4 flex h-48 items-end gap-1">
                @php
                    $maxCount = max(array_column($chartData, 'count'));
                    $maxCount = $maxCount > 0 ? $maxCount : 1;
                @endphp
                @foreach($chartData as $point)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="w-full rounded-t bg-blue-500 dark:bg-blue-400"
                             style="height: {{ ($point['count'] / $maxCount) * 100 }}%"
                             title="{{ $point['date'] }}: {{ $point['count'] }} orders"></div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Top products --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Top products</flux:heading>
            @if(empty($topProducts))
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No sales data for this period.</p>
            @else
                <table class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="pb-2 font-medium">Product</th>
                            <th class="pb-2 text-right font-medium">Sold</th>
                            <th class="pb-2 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($topProducts as $product)
                            <tr>
                                <td class="py-2 text-gray-900 dark:text-white">{{ $product['title'] }}</td>
                                <td class="py-2 text-right text-gray-500 dark:text-gray-400">{{ $product['units_sold'] }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">${{ number_format($product['revenue'] / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Conversion funnel --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Conversion funnel</flux:heading>
            @php
                $maxFunnel = max($funnelData['visits'], 1);
                $steps = [
                    ['label' => 'Visits', 'value' => $funnelData['visits'], 'color' => 'bg-blue-200 dark:bg-blue-900'],
                    ['label' => 'Add to Cart', 'value' => $funnelData['add_to_cart'], 'color' => 'bg-blue-300 dark:bg-blue-800'],
                    ['label' => 'Checkout Started', 'value' => $funnelData['checkout_started'], 'color' => 'bg-blue-400 dark:bg-blue-700'],
                    ['label' => 'Completed', 'value' => $funnelData['checkout_completed'], 'color' => 'bg-blue-600 dark:bg-blue-500'],
                ];
            @endphp
            <div class="mt-4 space-y-3">
                @foreach($steps as $step)
                    <div class="flex items-center gap-3">
                        <span class="w-32 text-sm text-gray-600 dark:text-gray-400">{{ $step['label'] }}</span>
                        <div class="flex-1">
                            <div class="{{ $step['color'] }} h-6 rounded" style="width: {{ $maxFunnel > 0 ? ($step['value'] / $maxFunnel) * 100 : 0 }}%"></div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ number_format($step['value']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
