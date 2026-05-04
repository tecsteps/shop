<section class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text class="mt-1">Sales, orders, and product movement for the selected store.</flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[560px]">
            <flux:select wire:model.live="dateRange" aria-label="Date range">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
                <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
                <flux:select.option value="custom">Custom</flux:select.option>
            </flux:select>

            <flux:input type="date" wire:model.live="customStartDate" :disabled="$dateRange !== 'custom'" aria-label="Custom range start" />
            <flux:input type="date" wire:model.live="customEndDate" :disabled="$dateRange !== 'custom'" aria-label="Custom range end" />
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total sales', 'value' => $this->formattedTotalSales, 'change' => $salesChange, 'icon' => 'banknotes'],
            ['label' => 'Orders', 'value' => number_format($ordersCount), 'change' => $ordersChange, 'icon' => 'shopping-bag'],
            ['label' => 'Average order', 'value' => $this->formattedAov, 'change' => $aovChange, 'icon' => 'receipt-percent'],
            ['label' => 'Visitors', 'value' => number_format($visitorsCount), 'change' => $visitorsChange, 'icon' => 'users'],
        ] as $metric)
            @php
                $change = (float) $metric['change'];
                $changeColor = $change > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($change < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-500 dark:text-zinc-400');
            @endphp

            <div wire:key="dashboard-kpi-{{ $metric['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $metric['value'] }}</div>
                    </div>

                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon :name="$metric['icon']" class="size-5" />
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-1 text-sm {{ $changeColor }}">
                    <flux:icon :name="$change >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-4" />
                    <span>{{ $change > 0 ? '+' : '' }}{{ number_format($change, 1) }}%</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)]">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">Orders over time</flux:heading>
                    <flux:text class="mt-1">Daily order count</flux:text>
                </div>
            </div>

            <div class="mt-6 flex h-56 items-end gap-1 overflow-hidden rounded-md border border-zinc-100 bg-zinc-50 px-3 pb-3 pt-6 dark:border-zinc-800 dark:bg-zinc-950">
                @foreach ($ordersChartData as $point)
                    @php
                        $height = $point['count'] === 0 ? 2 : max(8, (int) round(($point['count'] / $maxOrdersChartCount) * 100));
                    @endphp

                    <div wire:key="dashboard-chart-{{ $point['date'] }}" class="flex min-w-2 flex-1 flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t bg-sky-500 dark:bg-sky-400" style="height: {{ $height }}%"></div>
                        @if ($loop->first || $loop->last || $loop->iteration % 7 === 0)
                            <div class="hidden text-[11px] text-zinc-500 sm:block">{{ $point['label'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Top products</flux:heading>
            <flux:text class="mt-1">Ranked by order-line revenue</flux:text>

            <div class="mt-5 space-y-4">
                @forelse ($topProducts as $product)
                    <div wire:key="dashboard-top-product-{{ $product['title'] }}" class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-zinc-950 dark:text-white">{{ $product['title'] }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($product['units_sold']) }} sold</div>
                        </div>

                        <div class="text-right text-sm font-semibold text-zinc-950 dark:text-white">
                            {{ \App\Support\Money::format($product['revenue'], $storeCurrency) }}
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-10 text-center dark:border-zinc-700">
                        <flux:icon name="cube" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-3">No product sales in this range.</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Conversion funnel</flux:heading>
                <flux:text class="mt-1">Current range activity</flux:text>
            </div>
        </div>

        @php
            $maxFunnelValue = max(1, max($funnelData));
            $funnelSteps = [
                'visits' => 'Visits',
                'add_to_cart' => 'Carts',
                'checkout_started' => 'Checkouts',
                'checkout_completed' => 'Orders',
            ];
        @endphp

        <div class="mt-5 grid gap-4 md:grid-cols-4">
            @foreach ($funnelSteps as $key => $label)
                @php
                    $value = (int) $funnelData[$key];
                    $width = $value === 0 ? 2 : max(8, (int) round(($value / $maxFunnelValue) * 100));
                @endphp

                <div wire:key="dashboard-funnel-{{ $key }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ number_format($value) }}</div>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400" style="width: {{ $width }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
