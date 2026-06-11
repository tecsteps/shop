<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Dashboard')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>

        <flux:select wire:model.live="dateRange" size="sm" class="max-w-44" data-test="date-range-filter">
            <flux:select.option value="7">{{ __('Last 7 days') }}</flux:select.option>
            <flux:select.option value="30">{{ __('Last 30 days') }}</flux:select.option>
            <flux:select.option value="90">{{ __('Last 90 days') }}</flux:select.option>
        </flux:select>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        @foreach ([
            ['label' => __('Total sales'), 'value' => $formattedTotalSales, 'change' => $salesChange, 'test' => 'kpi-total-sales'],
            ['label' => __('Orders'), 'value' => number_format($ordersCount), 'change' => $ordersChange, 'test' => 'kpi-orders'],
            ['label' => __('Average order value'), 'value' => $formattedAov, 'change' => $aovChange, 'test' => 'kpi-aov'],
            ['label' => __('Conversion rate'), 'value' => number_format($conversionRate, 1).'%', 'change' => $conversionChange, 'test' => 'kpi-conversion'],
        ] as $tile)
            <x-admin.card data-test="{{ $tile['test'] }}">
                <flux:text>{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
                <div class="mt-2 flex items-center gap-1.5">
                    <flux:badge size="sm" :color="$tile['change'] >= 0 ? 'green' : 'red'">
                        {{ ($tile['change'] >= 0 ? '+' : '').number_format($tile['change'], 1) }}%
                    </flux:badge>
                    <flux:icon
                        :name="$tile['change'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'"
                        variant="micro"
                        class="{{ $tile['change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                    />
                    <flux:text class="text-xs">{{ __('vs previous period') }}</flux:text>
                </div>
            </x-admin.card>
        @endforeach
    </div>

    {{-- Orders over time (inline SVG line chart, no JS chart dependency) --}}
    <x-admin.card>
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Orders over time') }}</flux:heading>
            <flux:text class="text-xs">{{ __('Peak: :max orders/day', ['max' => $chart['max']]) }}</flux:text>
        </div>

        <div class="mt-4" wire:loading.class="opacity-50">
            <svg viewBox="0 0 600 180" preserveAspectRatio="none" class="h-44 w-full" role="img" aria-label="{{ __('Daily order counts') }}">
                <line x1="0" y1="175" x2="600" y2="175" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                <line x1="0" y1="90" x2="600" y2="90" class="stroke-zinc-100 dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="4 4" />
                <polygon points="{{ $chart['area'] }}" class="fill-blue-500/10 dark:fill-blue-400/10" />
                <polyline
                    points="{{ $chart['points'] }}"
                    fill="none"
                    class="stroke-blue-600 dark:stroke-blue-400"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                    vector-effect="non-scaling-stroke"
                />
            </svg>
            <div class="mt-1 flex justify-between text-xs text-zinc-400 dark:text-zinc-500">
                <span>{{ \Illuminate\Support\Carbon::parse($chart['days'][0]['date'])->format('M j') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse(end($chart['days'])['date'])->format('M j') }}</span>
            </div>
        </div>
    </x-admin.card>

    {{-- Recent orders --}}
    <x-admin.card class="!p-0">
        <div class="flex items-center justify-between p-6 pb-4">
            <flux:heading size="lg">{{ __('Recent orders') }}</flux:heading>
            <flux:button variant="ghost" size="sm" :href="route('admin.orders.index')" wire:navigate>
                {{ __('View all') }}
            </flux:button>
        </div>

        @if ($recentOrders->isEmpty())
            <div class="px-6 pb-6">
                <flux:text>{{ __('No orders yet.') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-6 py-2.5">{{ __('Order') }}</th>
                            <th class="px-6 py-2.5">{{ __('Date') }}</th>
                            <th class="px-6 py-2.5">{{ __('Customer') }}</th>
                            <th class="px-6 py-2.5">{{ __('Payment') }}</th>
                            <th class="px-6 py-2.5">{{ __('Fulfillment') }}</th>
                            <th class="px-6 py-2.5 text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, g:i A') }}</td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->customer?->name ?? __('Guest') }}</td>
                                <td class="px-6 py-3"><x-admin.status-badge :status="$order->financial_status" /></td>
                                <td class="px-6 py-3"><x-admin.status-badge :status="$order->fulfillment_status" /></td>
                                <td class="px-6 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ \App\Support\Storefront\PriceFormatter::format($order->total_amount, $order->currency) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</div>
