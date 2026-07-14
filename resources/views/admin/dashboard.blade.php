<div class="space-y-6">
    <x-admin.page-header title="Dashboard" description="A live view of your store’s sales and customer journey.">
        <x-slot:actions>
            <flux:select wire:model.live="dateRange" aria-label="Date range" class="min-w-44">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
                <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
                <flux:select.option value="custom">Custom range</flux:select.option>
            </flux:select>
        </x-slot:actions>
    </x-admin.page-header>

    @if($dateRange === 'custom')
        <x-admin.card class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model.live="customStartDate" type="date" label="Start date" />
            <flux:input wire:model.live="customEndDate" type="date" label="End date" />
        </x-admin.card>
    @endif

    @php
        $kpis = [
            ['label' => 'Total sales', 'value' => number_format($totalSales / 100, 2).' '.$adminStore->default_currency, 'change' => $salesChange],
            ['label' => 'Orders', 'value' => number_format($ordersCount), 'change' => $ordersChange],
            ['label' => 'Average order value', 'value' => number_format($averageOrderValue / 100, 2).' '.$adminStore->default_currency, 'change' => $aovChange],
            ['label' => 'Visitors', 'value' => number_format($visitorsCount), 'change' => $visitorsChange],
        ];
    @endphp
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Store performance">
        @foreach($kpis as $kpi)
            <x-admin.card wire:loading.class="opacity-50" wire:target="dateRange,customStartDate,customEndDate">
                <p class="text-sm font-medium text-slate-500">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight">{{ $kpi['value'] }}</p>
                <x-admin.status-badge class="mt-4" :status="$kpi['change'] >= 0 ? 'success' : 'failed'" :label="($kpi['change'] >= 0 ? '↑ ' : '↓ ').abs($kpi['change']).'% vs prior period'" />
            </x-admin.card>
        @endforeach
    </section>

    @php $chartMax = max(1, ...array_column($ordersChartData, 'count')); @endphp
    <x-admin.card title="Orders over time" description="Daily order count for the selected period.">
        <div class="flex h-64 items-end gap-1 overflow-x-auto pt-5" role="img" aria-label="Bar chart of daily orders">
            @foreach($ordersChartData as $point)
                <div class="group flex h-full min-w-4 flex-1 flex-col justify-end" title="{{ $point['date'] }}: {{ $point['count'] }} orders">
                    <div class="relative min-h-1 rounded-t bg-blue-600 transition hover:bg-blue-500" style="height: {{ max(2, ($point['count'] / $chartMax) * 100) }}%"><span class="sr-only">{{ $point['date'] }}: {{ $point['count'] }}</span></div>
                    @if($loop->first || $loop->last || $loop->iteration % 7 === 0)<span class="mt-2 whitespace-nowrap text-[10px] text-slate-400">{{ $point['date'] }}</span>@else<span class="mt-2 h-3"></span>@endif
                </div>
            @endforeach
        </div>
    </x-admin.card>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.card title="Top products">
            <div class="overflow-x-auto"><table class="admin-table"><thead><tr><th>Product</th><th>Units sold</th><th>Revenue</th></tr></thead><tbody>
                @forelse($topProducts as $product)<tr><td class="font-medium">{{ $product['title'] }}</td><td>{{ number_format($product['units_sold']) }}</td><td><x-admin.money :amount="$product['revenue']" :currency="$adminStore->default_currency" /></td></tr>
                @empty<tr><td colspan="3" class="py-10 text-center text-sm text-slate-500">No sales data for this period.</td></tr>@endforelse
            </tbody></table></div>
        </x-admin.card>
        <x-admin.card title="Conversion funnel">
            @php $funnelMax = max(1, $funnelData['visits']); $steps = ['visits' => 'Visits', 'add_to_cart' => 'Add to cart', 'checkout_started' => 'Checkout started', 'checkout_completed' => 'Checkout completed']; @endphp
            <div class="space-y-5">
                @foreach($steps as $key => $label)
                    <div><div class="mb-1.5 flex justify-between gap-4 text-sm"><span>{{ $label }}</span><span class="font-medium tabular-nums">{{ number_format($funnelData[$key]) }}</span></div><div class="h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-blue-600" style="width: {{ ($funnelData[$key] / $funnelMax) * 100 }}%"></div></div></div>
                @endforeach
            </div>
        </x-admin.card>
    </div>
</div>
