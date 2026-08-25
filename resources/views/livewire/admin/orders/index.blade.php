<div>
    <flux:heading size="xl">Orders</flux:heading>

    <div class="mt-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by order # or email..."
            class="max-w-sm"
        />
    </div>

    {{-- Filter tabs --}}
    <div class="mt-6 flex gap-6 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        @foreach ([
            'all' => 'All',
            'pending' => 'Pending',
            'paid' => 'Paid',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ] as $value => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'shrink-0 border-b-2 pb-3 text-sm transition',
                    'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $statusFilter === $value,
                    'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        <flux:card class="overflow-hidden">
            <flux:table :paginate="$this->orders">
                <flux:table.columns>
                    <flux:table.column
                        sortable
                        :sorted="$sortField === 'order_number'"
                        :direction="$sortField === 'order_number' ? $sortDirection : null"
                        wire:click="sortBy('order_number')"
                    >
                        Order #
                    </flux:table.column>
                    <flux:table.column
                        sortable
                        :sorted="$sortField === 'placed_at'"
                        :direction="$sortField === 'placed_at' ? $sortDirection : null"
                        wire:click="sortBy('placed_at')"
                    >
                        Date
                    </flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Payment</flux:table.column>
                    <flux:table.column>Fulfillment</flux:table.column>
                    <flux:table.column class="text-end">Total</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:table.cell>
                            <flux:table.cell>{{ $order->customer?->name ?? 'Guest' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $financialColors = ['pending' => 'zinc', 'paid' => 'green', 'partially_refunded' => 'yellow', 'refunded' => 'yellow', 'cancelled' => 'red'];
                                @endphp
                                <flux:badge :color="$financialColors[$order->financial_status] ?? 'zinc'" size="sm">
                                    {{ str_replace('_', ' ', ucfirst($order->financial_status)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $fulfillmentColors = ['unfulfilled' => 'zinc', 'partial' => 'yellow', 'fulfilled' => 'green'];
                                @endphp
                                <flux:badge :color="$fulfillmentColors[$order->fulfillment_status] ?? 'zinc'" size="sm">
                                    {{ ucfirst($order->fulfillment_status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-end font-medium text-zinc-800 dark:text-white">
                                {{ $this->formatMoney($order->total_amount) }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" align="center" class="py-10 text-zinc-400">
                                No orders match your filters.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
