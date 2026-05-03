<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text>Operational snapshot for {{ app('current_store')->name }}.</flux:text>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <flux:select wire:model.live="dateRange" label="Date range" size="sm">
                @foreach ($dateRangeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            @if ($dateRange === 'custom')
                <flux:input wire:model.live="customStartDate" type="date" label="Start" size="sm" />
                <flux:input wire:model.live="customEndDate" type="date" label="End" size="sm" />
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total sales', 'value' => $totalSales],
            ['label' => 'Orders', 'value' => number_format($ordersCount)],
            ['label' => 'Average order value', 'value' => $averageOrderValue],
            ['label' => 'Conversion rate', 'value' => $conversionRate],
        ] as $tile)
            <div wire:key="dashboard-kpi-{{ $tile['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tile['label'] }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $tile['value'] }}</div>
                <flux:badge color="green" class="mt-4">Live</flux:badge>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.8fr)]">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-4">
                <flux:heading size="lg">Orders over time</flux:heading>
                <flux:badge>{{ count($chartData) }} days with orders</flux:badge>
            </div>

            <div class="flex h-64 items-end gap-2">
                @forelse ($chartData as $point)
                    @php($height = max(8, min(100, $point['count'] * 24)))
                    <div wire:key="chart-{{ $point['date'] }}" class="flex flex-1 flex-col items-center gap-2">
                        <div class="w-full rounded-t bg-zinc-950 dark:bg-white" style="height: {{ $height }}%"></div>
                        <div class="text-[0.65rem] text-zinc-500">{{ \Illuminate\Support\Str::of($point['date'])->afterLast('-') }}</div>
                    </div>
                @empty
                    <div class="flex h-full w-full items-center justify-center rounded-md bg-zinc-50 text-sm text-zinc-500 dark:bg-zinc-950">
                        No orders in this range.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Top products</flux:heading>

            <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($topProducts as $product)
                    <div wire:key="top-product-{{ $loop->index }}" class="grid grid-cols-[1fr_auto] gap-4 py-3 text-sm">
                        <div>
                            <div class="font-medium">{{ $product->title_snapshot }}</div>
                            <div class="text-zinc-500">{{ (int) $product->units_sold }} sold</div>
                        </div>
                        <div class="font-semibold">{{ \Illuminate\Support\Number::currency(((int) $product->revenue) / 100, app('current_store')->default_currency) }}</div>
                    </div>
                @empty
                    <div class="py-10 text-sm text-zinc-500">No sales data for this period.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
            <flux:heading size="lg">Recent orders</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($recentOrders as $order)
                        <tr wire:key="recent-order-{{ $order->id }}">
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-5 py-4">{{ $order->customer?->name ?? $order->email }}</td>
                            <td class="px-5 py-4"><flux:badge>{{ $order->financial_status->value }}</flux:badge></td>
                            <td class="px-5 py-4 text-right font-medium">{{ \Illuminate\Support\Number::currency($order->total_amount / 100, $order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
