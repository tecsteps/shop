<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Orders')]]" />

    <flux:heading size="xl" level="1">{{ __('Orders') }}</flux:heading>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search by order # or email...')"
            class="max-w-xs"
            data-test="order-search"
        />

        <flux:input wire:model.live="dateFrom" type="date" size="sm" class="max-w-40" :aria-label="__('From date')" />
        <flux:text class="text-xs">{{ __('to') }}</flux:text>
        <flux:input wire:model.live="dateTo" type="date" size="sm" class="max-w-40" :aria-label="__('To date')" />
    </div>

    {{-- Status filter tabs --}}
    <div class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" role="tablist">
        @foreach ([
            'all' => __('All'),
            'pending' => __('Pending'),
            'paid' => __('Paid'),
            'fulfilled' => __('Fulfilled'),
            'cancelled' => __('Cancelled'),
            'refunded' => __('Refunded'),
        ] as $value => $label)
            <button
                type="button"
                wire:click="setStatusFilter('{{ $value }}')"
                role="tab"
                aria-selected="{{ $statusFilter === $value ? 'true' : 'false' }}"
                data-test="order-status-tab-{{ $value }}"
                class="-mb-px cursor-pointer border-b-2 px-4 py-2 text-sm whitespace-nowrap transition {{ $statusFilter === $value
                    ? 'border-blue-600 font-semibold text-zinc-900 dark:border-blue-400 dark:text-white'
                    : 'border-transparent font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Order') }}</th>
                        <th class="px-4 py-2.5">
                            <button type="button" wire:click="sortBy('placed_at')" class="flex cursor-pointer items-center gap-1 uppercase">
                                {{ __('Date') }}
                                @if ($sortField === 'placed_at')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-2.5">{{ __('Customer') }}</th>
                        <th class="px-4 py-2.5">{{ __('Payment') }}</th>
                        <th class="px-4 py-2.5">{{ __('Fulfillment') }}</th>
                        <th class="px-4 py-2.5 text-right">
                            <button type="button" wire:click="sortBy('total_amount')" class="ml-auto flex cursor-pointer items-center gap-1 uppercase">
                                {{ __('Total') }}
                                @if ($sortField === 'total_amount')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->customer?->name ?? __('Guest') }}</td>
                            <td class="px-4 py-3"><x-admin.status-badge :status="$order->financial_status" /></td>
                            <td class="px-4 py-3"><x-admin.status-badge :status="$order->fulfillment_status" /></td>
                            <td class="px-4 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">
                                {{ \App\Support\Storefront\PriceFormatter::format($order->total_amount, $order->currency) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No orders match your filters.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->orders->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->orders->links() }}
            </div>
        @endif
    </x-admin.card>
</div>
