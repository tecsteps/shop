@php
    use App\Support\Storefront\PriceFormatter;

    $kpis = $this->kpis;
    $chart = $this->chartData;
    $maxCount = max(1, collect($chart)->max('count'));
    $currency = $currentStore->default_currency ?? 'USD';
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Dashboard')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>

        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="dateRange" size="sm" class="w-44" data-test="date-range">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="last_7_days">{{ __('Last 7 days') }}</flux:select.option>
                <flux:select.option value="last_30_days">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom range') }}</flux:select.option>
            </flux:select>

            @if ($dateRange === 'custom')
                <flux:input type="date" wire:model.live="customStartDate" size="sm" />
                <flux:input type="date" wire:model.live="customEndDate" size="sm" />
            @endif
        </div>
    </div>

    {{-- KPI tiles. --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        <x-admin.card>
            <flux:text class="text-sm">{{ __('Total Sales') }}</flux:text>
            <flux:heading size="xl" class="mt-1" data-test="kpi-total-sales">{{ $this->formattedTotalSales }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['salesChange'] >= 0 ? 'green' : 'red'" :icon="$kpis['salesChange'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'">
                    {{ $kpis['salesChange'] >= 0 ? '+' : '' }}{{ $kpis['salesChange'] }}%
                </flux:badge>
            </div>
        </x-admin.card>

        <x-admin.card>
            <flux:text class="text-sm">{{ __('Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1" data-test="kpi-orders-count">{{ number_format($kpis['ordersCount']) }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['ordersChange'] >= 0 ? 'green' : 'red'" :icon="$kpis['ordersChange'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'">
                    {{ $kpis['ordersChange'] >= 0 ? '+' : '' }}{{ $kpis['ordersChange'] }}%
                </flux:badge>
            </div>
        </x-admin.card>

        <x-admin.card>
            <flux:text class="text-sm">{{ __('Avg Order Value') }}</flux:text>
            <flux:heading size="xl" class="mt-1" data-test="kpi-aov">{{ $this->formattedAov }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['aovChange'] >= 0 ? 'green' : 'red'" :icon="$kpis['aovChange'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'">
                    {{ $kpis['aovChange'] >= 0 ? '+' : '' }}{{ $kpis['aovChange'] }}%
                </flux:badge>
            </div>
        </x-admin.card>

        <x-admin.card>
            <flux:text class="text-sm">{{ __('Conversion Rate') }}</flux:text>
            <flux:heading size="xl" class="mt-1" data-test="kpi-conversion">{{ number_format($kpis['conversionRate'], 1) }}%</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" color="zinc">{{ __('Coming soon') }}</flux:badge>
            </div>
        </x-admin.card>
    </div>

    {{-- Orders over time chart. --}}
    <x-admin.card class="mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('Orders over time') }}</flux:heading>

        <div class="flex h-48 items-end gap-1" role="img" aria-label="{{ __('Daily order counts for the last 30 days') }}">
            @foreach ($chart as $day)
                <div class="group relative flex flex-1 flex-col items-center justify-end">
                    <div
                        class="w-full rounded-t bg-blue-500/80 transition-all hover:bg-blue-600 dark:bg-blue-500/60"
                        style="height: {{ max(2, (int) round(($day['count'] / $maxCount) * 100)) }}%"
                    ></div>
                    <span class="pointer-events-none absolute -top-7 hidden rounded bg-zinc-900 px-2 py-1 text-xs text-white group-hover:block dark:bg-zinc-700">
                        {{ \Illuminate\Support\Carbon::parse($day['date'])->format('M j') }}: {{ $day['count'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </x-admin.card>

    {{-- Recent orders. --}}
    <x-admin.card class="mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('Recent orders') }}</flux:heading>

        @if ($this->recentOrders->isEmpty())
            <flux:text>{{ __('No orders yet.') }}</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Customer') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->recentOrders as $order)
                        <flux:table.row :key="'order-'.$order->id">
                            <flux:table.cell>
                                <flux:link :href="route('admin.orders.show', $order)" wire:navigate>{{ $order->order_number }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:table.cell>
                            <flux:table.cell>{{ $order->customer?->name ?? __('Guest') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->financial_status->value === 'paid' ? 'green' : 'zinc'">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </x-admin.card>
</div>
