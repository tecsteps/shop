<div>
    <flux:heading size="xl">{{ __('Orders') }}</flux:heading>

    <div class="mt-4 flex flex-wrap items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search orders...') }}" class="w-64" icon="magnifying-glass" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
            <flux:select.option value="paid">{{ __('Paid') }}</flux:select.option>
            <flux:select.option value="fulfilled">{{ __('Fulfilled') }}</flux:select.option>
            <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
            <flux:select.option value="refunded">{{ __('Refunded') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="financialFilter" class="w-40">
            <flux:select.option value="">{{ __('Financial') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
            <flux:select.option value="paid">{{ __('Paid') }}</flux:select.option>
            <flux:select.option value="refunded">{{ __('Refunded') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="fulfillmentFilter" class="w-40">
            <flux:select.option value="">{{ __('Fulfillment') }}</flux:select.option>
            <flux:select.option value="unfulfilled">{{ __('Unfulfilled') }}</flux:select.option>
            <flux:select.option value="partial">{{ __('Partial') }}</flux:select.option>
            <flux:select.option value="fulfilled">{{ __('Fulfilled') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Order') }}</flux:table.column>
            <flux:table.column>{{ __('Customer') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Payment') }}</flux:table.column>
            <flux:table.column>{{ __('Fulfillment') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($orders as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="hover:underline">
                            {{ $order->order_number }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->customer?->name ?? $order->email }}</flux:table.cell>
                    <flux:table.cell>{{ $order->placed_at?->format('M d, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($order->status->value) {
                            'paid' => 'green',
                            'pending' => 'yellow',
                            'fulfilled' => 'blue',
                            'cancelled' => 'red',
                            'refunded' => 'zinc',
                            default => 'zinc',
                        }">{{ ucfirst($order->status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($order->financial_status->value) {
                            'paid' => 'green',
                            'pending' => 'yellow',
                            'refunded' => 'red',
                            'partially_refunded' => 'yellow',
                            'voided' => 'zinc',
                            default => 'zinc',
                        }">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($order->fulfillment_status->value) {
                            'fulfilled' => 'green',
                            'partial' => 'yellow',
                            'unfulfilled' => 'zinc',
                            default => 'zinc',
                        }">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">${{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <flux:text class="text-center">{{ __('No orders found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
