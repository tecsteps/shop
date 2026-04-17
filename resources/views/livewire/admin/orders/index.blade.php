<div class="space-y-4">
    <flux:heading size="xl">Orders</flux:heading>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:select wire:model.live="statusFilter">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="paid">Paid</flux:select.option>
            <flux:select.option value="partially_refunded">Partially refunded</flux:select.option>
            <flux:select.option value="refunded">Refunded</flux:select.option>
            <flux:select.option value="voided">Voided</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Order #</th>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Financial</th>
                    <th class="px-4 py-2">Fulfillment</th>
                    <th class="px-4 py-2">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $financialColor = match ($order->financial_status->value) {
                            'paid' => 'green',
                            'refunded' => 'yellow',
                            'voided' => 'red',
                            default => 'zinc',
                        };
                        $fulfillmentColor = match ($order->fulfillment_status->value) {
                            'fulfilled' => 'green',
                            'partial' => 'yellow',
                            default => 'zinc',
                        };
                    @endphp
                    <tr wire:key="order-{{ $order->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/orders/'.$order->id) }}" class="font-medium hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $order->email ?? 'Guest' }}</td>
                        <td class="px-4 py-2 text-neutral-500">{{ optional($order->placed_at)->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-2"><flux:badge color="{{ $financialColor }}" size="sm">{{ str_replace('_', ' ', $order->financial_status->value) }}</flux:badge></td>
                        <td class="px-4 py-2"><flux:badge color="{{ $fulfillmentColor }}" size="sm">{{ $order->fulfillment_status->value }}</flux:badge></td>
                        <td class="px-4 py-2">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-neutral-500">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
