<div>
    <flux:heading size="xl">Orders</flux:heading>

    <div class="mt-6 flex flex-wrap gap-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-36">
            <option value="all">All status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="fulfilled">Fulfilled</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </flux:select>
        <flux:select wire:model.live="financialFilter" class="w-36">
            <option value="all">All payment</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="refunded">Refunded</option>
            <option value="partially_refunded">Partial refund</option>
        </flux:select>
        <flux:select wire:model.live="fulfillmentFilter" class="w-40">
            <option value="all">All fulfillment</option>
            <option value="unfulfilled">Unfulfilled</option>
            <option value="partial">Partial</option>
            <option value="fulfilled">Fulfilled</option>
        </flux:select>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fulfillment</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $order->customer?->name ?? $order->email }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="match($order->financial_status->value) { 'paid' => 'green', 'refunded' => 'yellow', 'voided' => 'red', default => 'zinc' }">
                                {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="match($order->fulfillment_status->value) { 'fulfilled' => 'green', 'partial' => 'blue', default => 'zinc' }">
                                {{ ucfirst($order->fulfillment_status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($order->total_amount / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
