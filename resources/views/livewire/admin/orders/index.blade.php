<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Commerce</p>
            <flux:heading size="xl" level="1" class="mt-2">Orders</flux:heading>
            <flux:text class="mt-2" variant="subtle">Search, review, and manage every order for this store.</flux:text>
        </div>
        <div wire:loading class="text-sm text-zinc-500" role="status">Updating…</div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search order # or customer email…" aria-label="Search orders" class="min-w-72" />
    </div>

    <nav class="flex gap-5 overflow-x-auto border-b border-zinc-200" aria-label="Order status filters">
        @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
            <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="whitespace-nowrap border-b-2 px-1 py-3 text-sm {{ $statusFilter === $value ? 'border-blue-600 font-semibold text-blue-700' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-900' }}" aria-pressed="{{ $statusFilter === $value ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </nav>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <caption class="sr-only">Orders</caption>
            <thead class="bg-zinc-50">
                <tr>
                    @foreach (['order_number' => 'Order', 'placed_at' => 'Date'] as $field => $label)
                        <th scope="col" class="px-5 py-3"><button type="button" wire:click="sortBy('{{ $field }}')" class="inline-flex items-center gap-1 font-semibold hover:text-blue-700">{{ $label }} <span aria-hidden="true">{{ $sortField === $field ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    @endforeach
                    <th scope="col" class="px-5 py-3">Customer</th>
                    <th scope="col" class="px-5 py-3">Payment</th>
                    <th scope="col" class="px-5 py-3">Fulfillment</th>
                    <th scope="col" class="px-5 py-3 text-right"><button type="button" wire:click="sortBy('total_amount')" class="inline-flex items-center gap-1 font-semibold hover:text-blue-700">Total <span aria-hidden="true">{{ $sortField === 'total_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($orders as $order)
                    @php
                        $financialStatus = $order->financial_status->value;
                        $fulfillmentStatus = $order->fulfillment_status->value;
                    @endphp
                    <tr wire:key="admin-order-{{ $order->id }}" class="hover:bg-zinc-50">
                        <td class="whitespace-nowrap px-5 py-4"><a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-semibold text-blue-700 hover:underline">{{ $order->order_number }}</a></td>
                        <td class="whitespace-nowrap px-5 py-4 text-zinc-600">{{ $order->placed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="px-5 py-4"><div class="font-medium">{{ $order->customer?->name ?: 'Guest' }}</div><div class="text-xs text-zinc-500">{{ $order->customer?->email ?: $order->email }}</div></td>
                        <td class="px-5 py-4"><flux:badge :color="match ($financialStatus) { 'paid' => 'green', 'refunded', 'partially_refunded' => 'yellow', 'voided' => 'red', default => 'zinc' }">{{ str_replace('_', ' ', ucfirst($financialStatus)) }}</flux:badge></td>
                        <td class="px-5 py-4"><flux:badge :color="$fulfillmentStatus === 'fulfilled' ? 'green' : ($fulfillmentStatus === 'partial' || $fulfillmentStatus === 'partially_fulfilled' ? 'yellow' : 'zinc')">{{ str_replace('_', ' ', ucfirst($fulfillmentStatus)) }}</flux:badge></td>
                        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-zinc-500">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
