<div class="space-y-6">
    <div>
        <flux:heading size="xl">Orders</flux:heading>
        <flux:text>Review payment, fulfillment, and customer status.</flux:text>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-[1fr_14rem]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search order or email" icon="magnifying-glass" />
            <flux:select wire:model.live="financialStatus">
                <option value="all">All payments</option>
                @foreach ($financialStatuses as $status)
                    <option value="{{ $status->value }}">{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Payment</th>
                        <th class="px-5 py-3">Fulfillment</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr wire:key="admin-order-{{ $order->id }}">
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium hover:underline">{{ $order->order_number }}</a>
                                <div class="text-xs text-zinc-500">{{ $order->placed_at?->format('M j, Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-4">{{ $order->customer?->name ?? $order->email }}</td>
                            <td class="px-5 py-4"><flux:badge>{{ $order->financial_status->value }}</flux:badge></td>
                            <td class="px-5 py-4"><flux:badge>{{ $order->fulfillment_status->value }}</flux:badge></td>
                            <td class="px-5 py-4 text-right font-semibold">{{ \Illuminate\Support\Number::currency($order->total_amount / 100, $order->currency) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-zinc-500">No orders match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $orders->links() }}
        </div>
    </div>
</div>
