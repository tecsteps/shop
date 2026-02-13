<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Order') }} {{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1">{{ $order->placed_at?->format('M d, Y g:i A') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @if($this->canConfirmPayment)
                <flux:button wire:click="confirmPayment" variant="primary">{{ __('Confirm Payment') }}</flux:button>
            @endif
            @if($this->canFulfill)
                <flux:button wire:click="$set('showFulfillmentModal', true)">{{ __('Fulfill') }}</flux:button>
            @endif
            @if($this->canRefund)
                <flux:button wire:click="$set('showRefundModal', true)">{{ __('Refund') }}</flux:button>
            @endif
            @if($this->canCancel)
                <flux:button wire:click="$set('showCancelModal', true)" variant="danger">{{ __('Cancel') }}</flux:button>
            @endif
        </div>
    </div>

    @if($errors->any())
        <flux:callout variant="danger" class="mt-4">
            <flux:callout.heading>{{ $errors->first() }}</flux:callout.heading>
        </flux:callout>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <!-- Status badges -->
            <div class="flex items-center gap-2">
                <flux:badge :color="match($order->status->value) {
                    'paid' => 'green', 'pending' => 'yellow', 'fulfilled' => 'blue',
                    'cancelled' => 'red', 'refunded' => 'zinc', default => 'zinc',
                }">{{ ucfirst($order->status->value) }}</flux:badge>
                <flux:badge :color="match($order->financial_status->value) {
                    'paid' => 'green', 'pending' => 'yellow', 'refunded' => 'red',
                    'partially_refunded' => 'yellow', default => 'zinc',
                }">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                <flux:badge :color="match($order->fulfillment_status->value) {
                    'fulfilled' => 'green', 'partial' => 'yellow', default => 'zinc',
                }">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
            </div>

            <!-- Line items -->
            <div>
                <flux:heading size="lg">{{ __('Items') }}</flux:heading>
                <flux:table class="mt-2">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Product') }}</flux:table.column>
                        <flux:table.column>{{ __('SKU') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Qty') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Unit Price') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($order->lines as $line)
                            <flux:table.row :key="$line->id">
                                <flux:table.cell variant="strong">{{ $line->title_snapshot }}</flux:table.cell>
                                <flux:table.cell>{{ $line->sku_snapshot ?? '-' }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $line->quantity }}</flux:table.cell>
                                <flux:table.cell align="end">${{ number_format($line->unit_price_amount / 100, 2) }}</flux:table.cell>
                                <flux:table.cell align="end">${{ number_format($line->total_amount / 100, 2) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Order totals -->
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>${{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Discount') }}</span><span>-${{ number_format($order->discount_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Shipping') }}</span><span>${{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Tax') }}</span><span>${{ number_format($order->tax_amount / 100, 2) }}</span></div>
                    <flux:separator />
                    <div class="flex justify-between font-semibold"><span>{{ __('Total') }}</span><span>${{ number_format($order->total_amount / 100, 2) }}</span></div>
                </div>
            </div>

            <!-- Fulfillments -->
            @if($order->fulfillments->isNotEmpty())
                <div>
                    <flux:heading size="lg">{{ __('Fulfillments') }}</flux:heading>
                    @foreach($order->fulfillments as $fulfillment)
                        <div class="mt-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <flux:badge size="sm" :color="match($fulfillment->status->value) {
                                    'delivered' => 'green', 'shipped' => 'blue', default => 'yellow',
                                }">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                                <flux:text class="text-sm">{{ $fulfillment->created_at }}</flux:text>
                            </div>
                            @if($fulfillment->tracking_number)
                                <flux:text class="mt-1 text-sm">
                                    {{ $fulfillment->tracking_company }}: {{ $fulfillment->tracking_number }}
                                </flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Refunds -->
            @if($order->refunds->isNotEmpty())
                <div>
                    <flux:heading size="lg">{{ __('Refunds') }}</flux:heading>
                    @foreach($order->refunds as $refund)
                        <div class="mt-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <flux:badge size="sm" :color="$refund->status->value === 'processed' ? 'green' : 'red'">
                                    {{ ucfirst($refund->status->value) }}
                                </flux:badge>
                                <flux:text>${{ number_format($refund->amount / 100, 2) }}</flux:text>
                            </div>
                            @if($refund->reason)
                                <flux:text class="mt-1 text-sm">{{ $refund->reason }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sidebar info -->
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Customer') }}</flux:heading>
                <flux:text class="mt-1">{{ $order->customer?->name ?? 'Guest' }}</flux:text>
                <flux:text class="text-sm">{{ $order->email }}</flux:text>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Payment') }}</flux:heading>
                <flux:text class="mt-1">{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</flux:text>
            </div>
        </div>
    </div>

    <!-- Fulfillment Modal -->
    <flux:modal wire:model.live="showFulfillmentModal" name="fulfillment-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Fulfillment') }}</flux:heading>
                <flux:text class="mt-2">{{ __('This will fulfill all items in the order.') }}</flux:text>
            </div>
            <flux:input wire:model="trackingCompany" :label="__('Tracking company')" />
            <flux:input wire:model="trackingNumber" :label="__('Tracking number')" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showFulfillmentModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment">{{ __('Fulfill') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Refund Modal -->
    <flux:modal wire:model.live="showRefundModal" name="refund-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Refund') }}</flux:heading>
            </div>
            <flux:input wire:model="refundAmount" :label="__('Amount (cents)')" type="number" min="1" />
            <flux:input wire:model="refundReason" :label="__('Reason')" />
            <flux:checkbox wire:model="refundRestock" :label="__('Restock items')" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRefundModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="createRefund">{{ __('Refund') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Cancel Modal -->
    <flux:modal wire:model.live="showCancelModal" name="cancel-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Cancel Order') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to cancel this order? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showCancelModal', false)">{{ __('Keep order') }}</flux:button>
                <flux:button variant="danger" wire:click="cancelOrder">{{ __('Cancel order') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
