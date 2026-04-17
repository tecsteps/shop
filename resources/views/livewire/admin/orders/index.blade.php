<div>
    <div class="mb-6">
        <flux:heading size="xl">Orders</flux:heading>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by order # or email..."
            icon="magnifying-glass"
        />
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-4 flex gap-1 overflow-x-auto border-b border-gray-200 dark:border-gray-700">
        @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                    'border-blue-500 text-blue-600 dark:text-blue-400' => $statusFilter === $value,
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search,statusFilter">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="cursor-pointer px-4 py-3 font-medium text-gray-500 dark:text-gray-400" wire:click="sortBy('order_number')">Order #</th>
                    <th class="cursor-pointer px-4 py-3 font-medium text-gray-500 dark:text-gray-400" wire:click="sortBy('placed_at')">Date</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Payment</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Fulfillment</th>
                    <th class="cursor-pointer px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400" wire:click="sortBy('total_amount')">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="order-{{ $order->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $order->placed_at?->format('M j, Y g:i A') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $order->customer?->first_name ?? 'Guest' }} {{ $order->customer?->last_name ?? '' }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($order->financial_status->value) {
                                'paid' => 'green',
                                'refunded' => 'yellow',
                                'voided' => 'red',
                                default => 'zinc',
                            }" size="sm">
                                {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($order->fulfillment_status->value) {
                                'fulfilled' => 'green',
                                'partial' => 'yellow',
                                default => 'zinc',
                            }" size="sm">
                                {{ ucfirst($order->fulfillment_status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                            ${{ number_format($order->total_amount / 100, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
