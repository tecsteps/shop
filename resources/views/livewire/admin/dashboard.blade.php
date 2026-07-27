@php
    /** @var \App\Support\Money $money */
    $money = \App\Support\Money::class;

    $tiles = [
        ['label' => 'Total Sales', 'value' => $money::format($totalSales, $currency), 'change' => $salesChange],
        ['label' => 'Orders', 'value' => number_format($ordersCount), 'change' => $ordersChange],
        ['label' => 'Avg. Order Value', 'value' => $money::format($averageOrderValue, $currency), 'change' => $aovChange],
        ['label' => 'Conversion Rate', 'value' => $conversionRate === null ? '—' : $conversionRate.'%', 'change' => null],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">Dashboard</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-40" aria-label="Date range">
            <flux:select.option value="7">Last 7 days</flux:select.option>
            <flux:select.option value="30">Last 30 days</flux:select.option>
            <flux:select.option value="90">Last 90 days</flux:select.option>
        </flux:select>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50" wire:target="dateRange">
        @foreach ($tiles as $tile)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tile['label'] }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $tile['value'] }}</flux:heading>
                @if ($tile['change'] !== null)
                    <div class="mt-2 flex items-center gap-1">
                        <flux:badge size="sm" :color="$tile['change'] >= 0 ? 'green' : 'red'">
                            {{ $tile['change'] >= 0 ? '+' : '' }}{{ $tile['change'] }}%
                        </flux:badge>
                        <flux:icon :name="$tile['change'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'"
                                   class="size-4 {{ $tile['change'] >= 0 ? 'text-green-500' : 'text-red-500' }}" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Orders chart --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" level="2">Orders over time</flux:heading>

        <div class="relative mt-4" wire:loading.class="opacity-50" wire:target="dateRange">
            @if ($chart['max'] > 1 || array_sum(array_column($chart['days'], 'count')) > 0)
                <svg viewBox="0 0 600 160" class="h-40 w-full" role="img" aria-label="Daily order counts for the selected period" preserveAspectRatio="none">
                    <line x1="0" y1="150" x2="600" y2="150" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                    <polyline
                        fill="none"
                        class="stroke-blue-500"
                        stroke-width="2"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                        points="{{ $chart['points'] }}" />
                </svg>
                <div class="mt-2 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span>{{ $chart['days'][0]['date'] ?? '' }}</span>
                    <span>{{ $chart['days'][array_key_last($chart['days'])]['date'] ?? '' }}</span>
                </div>
            @else
                <p class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No orders in this period.</p>
            @endif
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" level="2">Recent orders</flux:heading>

        @if ($recentOrders->isEmpty())
            <p class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No orders yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4 font-medium">Order</th>
                            <th class="py-2 pr-4 font-medium">Date</th>
                            <th class="py-2 pr-4 font-medium">Customer</th>
                            <th class="py-2 pr-4 font-medium">Payment</th>
                            <th class="py-2 pr-4 font-medium">Fulfillment</th>
                            <th class="py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td class="py-2.5 pr-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $order->order_number }}</td>
                                <td class="py-2.5 pr-4 text-zinc-600 dark:text-zinc-300">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="py-2.5 pr-4 text-zinc-600 dark:text-zinc-300">{{ $order->customer?->name ?? 'Guest' }}</td>
                                <td class="py-2.5 pr-4">
                                    <flux:badge size="sm" :color="match ($order->financial_status) {
                                        \App\Enums\FinancialStatus::Paid => 'green',
                                        \App\Enums\FinancialStatus::Refunded, \App\Enums\FinancialStatus::PartiallyRefunded => 'yellow',
                                        \App\Enums\FinancialStatus::Voided => 'red',
                                        default => 'zinc',
                                    }">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <flux:badge size="sm" :color="match ($order->fulfillment_status) {
                                        \App\Enums\FulfillmentOrderStatus::Fulfilled => 'green',
                                        \App\Enums\FulfillmentOrderStatus::Partial => 'yellow',
                                        default => 'zinc',
                                    }">{{ ucfirst(str_replace('_', ' ', $order->fulfillment_status->value)) }}</flux:badge>
                                </td>
                                <td class="py-2.5 text-right text-zinc-900 dark:text-zinc-100">{{ $order->formattedTotal() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
