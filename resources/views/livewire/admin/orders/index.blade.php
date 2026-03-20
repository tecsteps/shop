<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">Orders</flux:heading>
    </div>

    {{-- Status Tabs --}}
    <div class="mb-4 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'voided' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition',
                    'border-blue-500 text-blue-600 dark:text-blue-400' => $statusFilter === $value,
                    'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." icon="magnifying-glass" />
    </div>

    {{-- Orders Table --}}
    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Order #</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'placed_at'" :direction="$sortDirection" wire:click="sortBy('placed_at')">Date</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Payment</flux:table.column>
            <flux:table.column>Fulfill</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'total_amount'" :direction="$sortDirection" wire:click="sortBy('total_amount')">Total</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($this->orders as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                            {{ $order->order_number }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $order->placed_at ? \Carbon\Carbon::parse($order->placed_at)->format('M d, Y') : '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $order->customer?->name ?? $order->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($order->financial_status->value) { 'paid' => 'green', 'pending' => 'zinc', 'refunded' => 'yellow', 'partially_refunded' => 'yellow', 'voided' => 'red', default => 'zinc' }">
                            {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($order->fulfillment_status->value) { 'fulfilled' => 'green', 'partial' => 'yellow', 'unfulfilled' => 'zinc' }">
                            {{ ucfirst($order->fulfillment_status->value) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $this->formatCurrency($order->total_amount) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">
                        <flux:text class="text-zinc-500">No orders found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
