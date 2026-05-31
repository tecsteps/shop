@php
    use App\Support\Storefront\PriceFormatter;

    $financialColors = ['pending' => 'zinc', 'paid' => 'green', 'refunded' => 'yellow', 'partially_refunded' => 'yellow', 'voided' => 'red'];
    $fulfillmentColors = ['unfulfilled' => 'zinc', 'partial' => 'yellow', 'fulfilled' => 'green'];

    $tabs = [
        'all' => __('All'),
        'pending' => __('Pending'),
        'paid' => __('Paid'),
        'fulfilled' => __('Fulfilled'),
        'cancelled' => __('Cancelled'),
        'refunded' => __('Refunded'),
    ];
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Orders')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Orders') }}</flux:heading>

    <div class="mb-4 max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search by order # or email...')"
            data-test="order-search"
        />
    </div>

    {{-- Filter tabs. --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
        @foreach ($tabs as $value => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'border-b-2 px-4 py-2 text-sm transition',
                    'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $statusFilter === $value,
                    'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $statusFilter !== $value,
                ])
                role="tab"
                aria-selected="{{ $statusFilter === $value ? 'true' : 'false' }}"
                data-test="order-tab-{{ $value }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div wire:loading.class="opacity-50">
        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortField === 'order_number'" :direction="$sortDirection" wire:click="sortBy('order_number')">{{ __('Order #') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'placed_at'" :direction="$sortDirection" wire:click="sortBy('placed_at')">{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Payment') }}</flux:table.column>
                <flux:table.column>{{ __('Fulfillment') }}</flux:table.column>
                <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="'order-'.$order->id">
                        <flux:table.cell variant="strong">
                            <flux:link :href="route('admin.orders.show', $order)" wire:navigate>{{ $order->order_number }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:table.cell>
                        <flux:table.cell>{{ $order->customer?->name ?? __('Guest') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$financialColors[$order->financial_status->value] ?? 'zinc'">
                                {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$fulfillmentColors[$order->fulfillment_status->value] ?? 'zinc'">
                                {{ ucfirst($order->fulfillment_status->value) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">{{ __('No orders found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
