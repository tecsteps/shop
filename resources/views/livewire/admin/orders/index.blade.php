<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search orders..." icon="magnifying-glass" class="w-64" />
    </div>

    <div class="mb-4 flex gap-2">
        @foreach(['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'] as $value => $label)
            <flux:button wire:click="$set('status', '{{ $value }}')" :variant="$status === $value ? 'primary' : 'ghost'" size="sm">{{ $label }}</flux:button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fulfillment</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($orders as $order)
                        <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:click="$dispatch('navigate', { url: '{{ route('admin.orders.show', $order) }}' })" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">#{{ $order->order_number }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') ?? $order->created_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->email }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <flux:badge size="sm" :variant="match($order->financial_status?->value) { 'paid' => 'primary', 'pending' => 'warning', 'refunded' => 'danger', default => 'outline' }">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status?->value ?? 'unknown')) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <flux:badge size="sm" :variant="match($order->fulfillment_status?->value) { 'fulfilled' => 'primary', 'partial' => 'warning', default => 'outline' }">
                                    {{ ucfirst($order->fulfillment_status?->value ?? 'unfulfilled') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($order->total / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
