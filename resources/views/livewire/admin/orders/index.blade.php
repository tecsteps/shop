<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Orders') }}</flux:heading>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by order number or email...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="financialFilter" class="w-full sm:w-48">
            <flux:select.option value="all">{{ __('All payments') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
            <flux:select.option value="paid">{{ __('Paid') }}</flux:select.option>
            <flux:select.option value="refunded">{{ __('Refunded') }}</flux:select.option>
            <flux:select.option value="partially_refunded">{{ __('Partially refunded') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="fulfillmentFilter" class="w-full sm:w-48">
            <flux:select.option value="all">{{ __('All fulfillments') }}</flux:select.option>
            <flux:select.option value="unfulfilled">{{ __('Unfulfilled') }}</flux:select.option>
            <flux:select.option value="partial">{{ __('Partial') }}</flux:select.option>
            <flux:select.option value="fulfilled">{{ __('Fulfilled') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Order') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Customer') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Total') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Payment') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Fulfillment') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @foreach($this->orders as $order)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-accent hover:underline" wire:navigate>
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="p-3">{{ $order->email }}</td>
                                <td class="p-3">${{ number_format($order->total_amount / 100, 2) }}</td>
                                <td class="p-3">
                                    <flux:badge size="sm" :color="match($order->financial_status->value) {
                                        'paid' => 'green',
                                        'pending' => 'yellow',
                                        'refunded' => 'red',
                                        'partially_refunded' => 'orange',
                                        'authorized' => 'blue',
                                        default => 'zinc',
                                    }">{{ str_replace('_', ' ', ucfirst($order->financial_status->value)) }}</flux:badge>
                                </td>
                                <td class="p-3">
                                    <flux:badge size="sm" :color="match($order->fulfillment_status->value) {
                                        'fulfilled' => 'green',
                                        'partial' => 'yellow',
                                        'unfulfilled' => 'zinc',
                                        default => 'zinc',
                                    }">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                                </td>
                                <td class="p-3 text-zinc-500">{{ $order->placed_at?->diffForHumans() ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $this->orders->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="shopping-bag" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No orders yet') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Orders will appear here when customers place them.') }}</flux:text>
            </div>
        @endif
    </div>
</div>
