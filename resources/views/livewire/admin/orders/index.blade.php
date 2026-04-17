<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Orders</flux:heading>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." icon="magnifying-glass" />
    </div>

    {{-- Status filter tabs --}}
    <div class="flex gap-1 mb-6 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors {{ $statusFilter === $value ? 'border-zinc-900 dark:border-white text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Orders table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('order_number')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                Order #
                                @if ($sortField === 'order_number')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('placed_at')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                Date
                                @if ($sortField === 'placed_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Customer</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Payment</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Fulfillment</th>
                        <th class="text-right px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('total_amount')" class="flex items-center gap-1 ml-auto hover:text-zinc-900 dark:hover:text-white">
                                Total
                                @if ($sortField === 'total_amount')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:underline">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $order->placed_at?->format('M j, Y g:i A') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $order->customer?->name ?? $order->email ?? 'Guest' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $financialColor = match($order->financial_status) {
                                        \App\Enums\FinancialStatus::Paid => 'green',
                                        \App\Enums\FinancialStatus::Refunded => 'yellow',
                                        \App\Enums\FinancialStatus::PartiallyRefunded => 'yellow',
                                        \App\Enums\FinancialStatus::Voided => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge color="{{ $financialColor }}" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $fulfillColor = match($order->fulfillment_status) {
                                        \App\Enums\FulfillmentStatus::Fulfilled => 'green',
                                        \App\Enums\FulfillmentStatus::Partial => 'yellow',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge color="{{ $fulfillColor }}" size="sm">
                                    {{ ucfirst($order->fulfillment_status->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">
                                {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency ?? 'EUR' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->orders->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->orders->links() }}
            </div>
        @endif
    </div>
</div>
