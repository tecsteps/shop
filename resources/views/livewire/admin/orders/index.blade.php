<div>
    <div class="mb-6">
        <flux:heading size="xl">Orders</flux:heading>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." icon="magnifying-glass" />
    </div>

    {{-- Status filter tabs --}}
    <div class="mb-4 flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium transition-colors',
                    'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' => $statusFilter === $value,
                    'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Orders table --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800" wire:loading.class="opacity-50">
        @if ($this->orders->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No orders found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Customer</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Payment</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Fulfillment</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->orders as $order)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y g:i A') ?? '--' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->customer?->name ?? 'Guest' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $financialColor = match($order->financial_status->value) {
                                        'paid' => 'green',
                                        'refunded' => 'yellow',
                                        'partially_refunded' => 'yellow',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$financialColor" size="sm">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $fulfillColor = match($order->fulfillment_status->value) {
                                        'fulfilled' => 'green',
                                        'partial' => 'yellow',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$fulfillColor" size="sm">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">${{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">
                {{ $this->orders->links() }}
            </div>
        @endif
    </div>
</div>
