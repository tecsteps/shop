<div>
    <x-slot:title>Orders</x-slot:title>

    <flux:heading size="xl">Orders</flux:heading>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order number or email..." icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="fulfilled">Fulfilled</option>
            <option value="cancelled">Cancelled</option>
        </flux:select>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fulfillment</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($orders as $order)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $order->customer?->name ?? $order->email ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($order->status->value) { 'pending' => 'yellow', 'paid' => 'green', 'fulfilled' => 'green', 'cancelled' => 'red', default => 'zinc' }">
                                {{ ucfirst($order->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($order->financial_status->value) { 'pending' => 'yellow', 'paid' => 'green', 'refunded' => 'red', 'voided' => 'red', default => 'zinc' }">
                                {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($order->fulfillment_status->value) { 'unfulfilled' => 'zinc', 'partial' => 'yellow', 'fulfilled' => 'green', default => 'zinc' }">
                                {{ ucfirst($order->fulfillment_status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            {{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                            {{ $order->placed_at?->format('M d, Y') ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
