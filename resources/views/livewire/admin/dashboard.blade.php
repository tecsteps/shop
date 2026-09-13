<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Dashboard</flux:heading>

        <div class="flex flex-wrap items-center gap-2">
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
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        @php
            $tiles = [
                ['label' => 'Total Sales', 'value' => $this->money($totalSales), 'change' => $salesChange],
                ['label' => 'Orders', 'value' => number_format($ordersCount), 'change' => $ordersChange],
                ['label' => 'Avg Order Value', 'value' => $this->money($averageOrderValue), 'change' => $aovChange],
                ['label' => 'Visitors', 'value' => number_format($visitorsCount), 'change' => $visitorsChange],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900" wire:key="kpi-{{ $loop->index }}">
                <flux:text variant="subtle">{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
                <div class="mt-2 flex items-center gap-1">
                    <flux:badge :color="$tile['change'] >= 0 ? 'green' : 'red'">{{ ($tile['change'] >= 0 ? '+' : '').$tile['change'] }}%</flux:badge>
                    <flux:icon :icon="$tile['change'] >= 0 ? 'arrow-up' : 'arrow-down'" variant="mini" class="size-4 {{ $tile['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Orders over time</flux:heading>
        <div
            class="relative mt-4"
            x-data="{ data: @js($ordersChartData) }"
        >
            @if (count($ordersChartData) === 0)
                <flux:text variant="subtle">No orders in this period.</flux:text>
            @else
                <div class="flex h-48 items-end gap-1" role="img" aria-label="Daily order counts chart">
                    @php $max = max(1, max(array_column($ordersChartData, 'count'))); @endphp
                    @foreach ($ordersChartData as $point)
                        <div
                            class="flex-1 rounded-t bg-zinc-900 dark:bg-white"
                            style="height: {{ max(4, (int) ($point['count'] / $max * 100)) }}%"
                            title="{{ $point['date'] }}: {{ $point['count'] }} orders"
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
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Top products</flux:heading>
            @if (count($topProducts) === 0)
                <flux:text variant="subtle" class="mt-4">No sales data for this period.</flux:text>
            @else
                <table class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Product Title</th>
                            <th class="pb-2 font-medium">Units Sold</th>
                            <th class="pb-2 font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="top-{{ $product['product_id'] }}">
                                <td class="py-2">{{ $product['title'] }}</td>
                                <td class="py-2">{{ $product['units_sold'] }}</td>
                                <td class="py-2">{{ $this->money($product['revenue_amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
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
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Recent orders</flux:heading>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium underline" wire:navigate>View all</a>
        </div>
        @if ($recentOrders->isEmpty())
            <flux:text variant="subtle" class="mt-4">No orders yet.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Order</th>
                            <th class="pb-2 font-medium">Customer</th>
                            <th class="pb-2 font-medium">Status</th>
                            <th class="pb-2 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="recent-{{ $order->getKey() }}">
                                <td class="py-2"><a href="{{ route('admin.orders.show', $order) }}" class="underline" wire:navigate>{{ $order->order_number }}</a></td>
                                <td class="py-2">{{ $order->customer?->name ?? 'Guest' }}</td>
                                <td class="py-2"><flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : 'zinc'">{{ ucfirst($order->financial_status->value) }}</flux:badge></td>
                                <td class="py-2">{{ $this->money($order->total_amount, $order->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
