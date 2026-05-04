<section class="space-y-6">
    <div>
        <flux:heading size="xl">Orders</flux:heading>
        <flux:text class="mt-1">Review orders, payment status, and fulfillment progress.</flux:text>
    </div>

    <div class="grid gap-3 xl:grid-cols-[1fr_160px_190px_190px_150px_150px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search orders or customers..." aria-label="Search orders" />

        <flux:select wire:model.live="statusFilter" aria-label="Order status filter">
            <flux:select.option value="all">All orders</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ Str::title($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="financialStatusFilter" aria-label="Financial status filter">
            <flux:select.option value="all">All financial</flux:select.option>
            @foreach ($financialStatuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ Str::headline($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="fulfillmentStatusFilter" aria-label="Fulfillment status filter">
            <flux:select.option value="all">All fulfillment</flux:select.option>
            @foreach ($fulfillmentStatuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ Str::headline($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="dateFrom" aria-label="Placed from" />
        <flux:input type="date" wire:model.live="dateTo" aria-label="Placed to" />
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Fulfillment</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Placed</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        @php
                            $financialColor = match ($order->financial_status->value) {
                                'paid' => 'green',
                                'partially_refunded' => 'amber',
                                'refunded', 'voided' => 'red',
                                default => 'zinc',
                            };
                            $fulfillmentColor = match ($order->fulfillment_status->value) {
                                'fulfilled' => 'green',
                                'partial' => 'amber',
                                default => 'zinc',
                            };
                        @endphp

                        <tr wire:key="admin-order-{{ $order->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-xs text-zinc-500">{{ Str::headline($order->status->value) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $order->customer?->name ?: 'Guest' }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$financialColor">{{ Str::headline($order->financial_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$fulfillmentColor">{{ Str::headline($order->fulfillment_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">{{ $order->lines_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="justify-end" />
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $order->placed_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="shopping-bag" class="size-6" />
                                    </div>
                                    <flux:heading size="lg">No orders found</flux:heading>
                                    <flux:text>Orders will appear here after checkout completion.</flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $orders->links() }}
</section>
