<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Orders</flux:heading>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by order # or email..." aria-label="Search orders" class="max-w-sm" />
    </div>

    <div class="mb-4 flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="Filter by status">
        @php
            $tabs = ['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
        @endphp
        @foreach ($tabs as $value => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                role="tab"
                aria-selected="{{ $this->statusFilter === $value ? 'true' : 'false' }}"
                class="px-3 py-2 text-sm {{ $this->statusFilter === $value ? 'border-b-2 border-zinc-900 font-semibold dark:border-white' : 'text-zinc-500 dark:text-zinc-400' }}"
                wire:key="tab-{{ $value }}"
            >{{ $label }}</button>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium"><button type="button" wire:click="sortBy('order_number')" class="font-medium">Order #</button></th>
                    <th class="px-4 py-3 font-medium"><button type="button" wire:click="sortBy('placed_at')" class="font-medium">Date</button></th>
                    <th class="px-4 py-3 font-medium">Customer</th>
                    <th class="px-4 py-3 font-medium">Payment</th>
                    <th class="px-4 py-3 font-medium">Fulfillment</th>
                    <th class="px-4 py-3 font-medium"><button type="button" wire:click="sortBy('total_amount')" class="font-medium">Total</button></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="order-{{ $order->getKey() }}">
                        <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium underline" wire:navigate>{{ $order->order_number }}</a></td>
                        <td class="px-4 py-3">{{ $order->placed_at?->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3">{{ $order->customer?->name ?? 'Guest' }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match ($order->financial_status->value) { 'paid' => 'green', 'refunded' => 'yellow', 'cancelled' => 'red', default => 'zinc' }">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match ($order->fulfillment_status->value) { 'fulfilled' => 'green', 'partial' => 'yellow', default => 'zinc' }">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $this->money($order->total_amount, $order->currency) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center"><flux:text variant="subtle">No orders found.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
