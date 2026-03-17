<div>
    <div class="mb-6">
        <flux:heading size="xl">Analytics</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <flux:select wire:model.live="dateRange" class="w-40">
            <option value="today">Today</option>
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
            <option value="custom">Custom</option>
        </flux:select>
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
        <flux:button variant="ghost" wire:click="exportCsv" icon="arrow-down-tray">
            Export CSV
        </flux:button>
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
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($totalSales / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500">Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($averageOrderValue / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:text class="text-sm text-gray-500">Conversion Rate</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($conversionRate, 1) }}%</flux:heading>
        </div>
    </div>

    {{-- Sales Chart --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="lg" class="mb-4">Sales over time</flux:heading>
        <div
            x-data="{
                chart: null,
                init() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    const data = @js($salesChartData);
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(d => d.date),
                            datasets: [{
                                label: 'Revenue',
                                data: data.map(d => d.revenue / 100),
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
                                y: { beginAtZero: true },
                                x: { display: true, ticks: { maxTicksLimit: 10 } },
                            }
                        }
                    });
                }
            }"
            wire:ignore
            class="h-72"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="lg" class="mb-4">Top products</flux:heading>
        @if (count($topProducts) > 0)
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 font-medium text-gray-500">Rank</th>
                        <th class="pb-2 font-medium text-gray-500">Product</th>
                        <th class="pb-2 text-right font-medium text-gray-500">Units Sold</th>
                        <th class="pb-2 text-right font-medium text-gray-500">Revenue</th>
                        <th class="pb-2 text-right font-medium text-gray-500">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topProducts as $product)
                        <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="top-{{ $product['rank'] }}">
                            <td class="py-2">{{ $product['rank'] }}</td>
                            <td class="py-2">{{ $product['title'] }}</td>
                            <td class="py-2 text-right">{{ number_format($product['units_sold']) }}</td>
                            <td class="py-2 text-right">${{ number_format($product['revenue'] / 100, 2) }}</td>
                            <td class="py-2 text-right">{{ $product['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <flux:text class="text-gray-500">No sales data for this period.</flux:text>
        @endif
    </div>
</div>
