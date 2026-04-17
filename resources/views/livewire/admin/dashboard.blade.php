<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>

        <flux:dropdown>
            <flux:button variant="ghost" icon="calendar-days">
                {{ match($dateRange) {
                    'today' => 'Today',
                    'last_7_days' => 'Last 7 days',
                    'last_30_days' => 'Last 30 days',
                    'custom' => 'Custom range',
                    default => 'Last 30 days',
                } }}
                <flux:icon name="chevron-down" variant="mini" class="ml-1 h-4 w-4" />
            </flux:button>

            <flux:menu>
                <flux:menu.item wire:click="$set('dateRange', 'today')">Today</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'last_7_days')">Last 7 days</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'last_30_days')">Last 30 days</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'custom')">Custom range</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    @if ($dateRange === 'custom')
        <div class="mb-6 flex gap-4">
            <flux:field>
                <flux:label>Start date</flux:label>
                <flux:input type="date" wire:model.live="customStartDate" />
            </flux:field>
            <flux:field>
                <flux:label>End date</flux:label>
                <flux:input type="date" wire:model.live="customEndDate" />
            </flux:field>
        </div>
    @endif

    {{-- KPI Tiles --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50" wire:target="dateRange,customStartDate,customEndDate">
        {{-- Total Sales --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">${{ $this->formattedTotalSales }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$salesChange >= 0 ? 'green' : 'red'" size="sm">
                    <flux:icon :name="$salesChange >= 0 ? 'arrow-up' : 'arrow-down'" variant="mini" class="mr-1 h-3 w-3" />
                    {{ abs($salesChange) }}%
                </flux:badge>
            </div>
        </div>

        {{-- Orders --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$ordersChange >= 0 ? 'green' : 'red'" size="sm">
                    <flux:icon :name="$ordersChange >= 0 ? 'arrow-up' : 'arrow-down'" variant="mini" class="mr-1 h-3 w-3" />
                    {{ abs($ordersChange) }}%
                </flux:badge>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">${{ $this->formattedAov }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$aovChange >= 0 ? 'green' : 'red'" size="sm">
                    <flux:icon :name="$aovChange >= 0 ? 'arrow-up' : 'arrow-down'" variant="mini" class="mr-1 h-3 w-3" />
                    {{ abs($aovChange) }}%
                </flux:badge>
            </div>
        </div>

        {{-- Visitors --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Visitors</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($visitorsCount) }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$visitorsChange >= 0 ? 'green' : 'red'" size="sm">
                    <flux:icon :name="$visitorsChange >= 0 ? 'arrow-up' : 'arrow-down'" variant="mini" class="mr-1 h-3 w-3" />
                    {{ abs($visitorsChange) }}%
                </flux:badge>
            </div>
        </div>
    </div>

    {{-- Orders Chart --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="lg" class="mb-4">Orders over time</flux:heading>
        <div
            x-data="{
                chart: null,
                init() {
                    this.renderChart();
                },
                renderChart() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    if (this.chart) this.chart.destroy();
                    const data = @js($ordersChartData);
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(d => d.date),
                            datasets: [{
                                label: 'Orders',
                                data: data.map(d => d.count),
                                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                                x: { display: true, ticks: { maxTicksLimit: 10 } },
                            }
                        }
                    });
                }
            }"
            x-init="init()"
            wire:ignore
            class="h-64"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    {{-- Bottom grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top Products --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="lg" class="mb-4">Top products</flux:heading>
            @if (count($topProducts) > 0)
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Product</th>
                            <th class="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Sold</th>
                            <th class="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="top-{{ $loop->index }}">
                                <td class="py-2">{{ $product['title'] }}</td>
                                <td class="py-2 text-right">{{ number_format($product['units_sold']) }}</td>
                                <td class="py-2 text-right">${{ number_format($product['revenue'] / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <flux:text class="text-gray-500 dark:text-gray-400">No sales data for this period.</flux:text>
            @endif
        </div>

        {{-- Conversion Funnel --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="lg" class="mb-4">Conversion funnel</flux:heading>
            @php
                $maxFunnel = max($funnelData['visits'], 1);
            @endphp
            <div class="space-y-3">
                @foreach ([
                    'visits' => 'Visits',
                    'add_to_cart' => 'Add to Cart',
                    'checkout_started' => 'Checkout Started',
                    'checkout_completed' => 'Checkout Completed',
                ] as $key => $label)
                    <div class="flex items-center gap-4">
                        <span class="w-36 shrink-0 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <div class="flex-1">
                            <div class="h-6 rounded bg-gray-100 dark:bg-gray-800">
                                <div
                                    class="h-6 rounded bg-blue-500"
                                    style="width: {{ $maxFunnel > 0 ? ($funnelData[$key] / $maxFunnel * 100) : 0 }}%"
                                ></div>
                            </div>
                        </div>
                        <span class="w-16 text-right text-sm font-medium">{{ number_format($funnelData[$key]) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
