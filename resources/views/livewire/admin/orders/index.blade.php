<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Orders</flux:heading>
    </div>

    @if (! $hasOrders)
        {{-- Empty state (spec 03 §19.1) --}}
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="shopping-bag" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">No orders yet</flux:heading>
            <flux:text class="mt-1">Orders placed in your storefront will appear here.</flux:text>
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search by order # or email..." class="w-full sm:w-72" aria-label="Search orders" />

            <flux:select wire:model.live="financialFilter" class="w-44" aria-label="Financial status filter">
                <flux:select.option value="all">All payments</flux:select.option>
                @foreach (\App\Enums\FinancialStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ Str::headline($status->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="fulfillmentFilter" class="w-44" aria-label="Fulfillment status filter">
                <flux:select.option value="all">All fulfillments</flux:select.option>
                @foreach (\App\Enums\FulfillmentOrderStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ Str::headline($status->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model.live="dateFrom" aria-label="Placed from" />
                <flux:text class="text-zinc-400">–</flux:text>
                <flux:input type="date" wire:model.live="dateTo" aria-label="Placed to" />
            </div>
        </div>

        {{-- Status tabs (spec 03 §7) --}}
        <div class="flex gap-4 border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="Status filter">
            @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
                <button type="button"
                        wire:click="$set('statusFilter', '{{ $value }}')"
                        role="tab"
                        aria-selected="{{ $statusFilter === $value ? 'true' : 'false' }}"
                        class="-mb-px border-b-2 px-1 pb-2 text-sm {{ $statusFilter === $value ? 'border-zinc-900 font-semibold text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('order_number')" class="inline-flex items-center gap-1 uppercase">
                                Order
                                @if ($sortField === 'order_number')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('placed_at')" class="inline-flex items-center gap-1 uppercase">
                                Date
                                @if ($sortField === 'placed_at')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Payment</th>
                        <th class="px-4 py-3 font-medium">Fulfillment</th>
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('total_amount')" class="inline-flex items-center gap-1 uppercase">
                                Total
                                @if ($sortField === 'total_amount')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $order->placed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $order->customer?->name ?? 'Guest' }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($order->financial_status) {
                                    \App\Enums\FinancialStatus::Paid => 'green',
                                    \App\Enums\FinancialStatus::PartiallyRefunded, \App\Enums\FinancialStatus::Refunded => 'yellow',
                                    \App\Enums\FinancialStatus::Voided => 'red',
                                    default => 'zinc',
                                }">{{ Str::headline($order->financial_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($order->fulfillment_status) {
                                    \App\Enums\FulfillmentOrderStatus::Fulfilled => 'green',
                                    \App\Enums\FulfillmentOrderStatus::Partial => 'yellow',
                                    default => 'zinc',
                                }">{{ Str::headline($order->fulfillment_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $order->formattedTotal() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No orders match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>
