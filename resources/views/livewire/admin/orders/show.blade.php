<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
            <div class="mt-1 flex items-center gap-2">
                <flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : 'yellow'">
                    {{ $order->financial_status->value }}
                </flux:badge>
                <flux:badge :color="$order->fulfillment_status->value === 'fulfilled' ? 'green' : 'zinc'">
                    {{ $order->fulfillment_status->value }}
                </flux:badge>
                <span class="text-sm text-zinc-500">{{ $order->placed_at?->format('M d, Y H:i') }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            @if ($order->payment_method->value === 'bank_transfer' && $order->financial_status->value === 'pending')
                <flux:button wire:click="confirmBankTransfer" variant="primary" data-test="confirm-bank-transfer">
                    Confirm payment
                </flux:button>
            @endif
            @if (in_array($order->financial_status->value, ['paid', 'partially_refunded'], true) && $order->fulfillment_status->value !== 'fulfilled')
                <flux:button wire:click="openFulfillModal" variant="primary" data-test="open-fulfill-modal">
                    Fulfill items
                </flux:button>
            @endif
            @if (in_array($order->financial_status->value, ['paid', 'partially_refunded'], true) && $order->refundableAmount() > 0)
                <flux:button wire:click="openRefundModal" data-test="open-refund-modal">
                    Refund
                </flux:button>
            @endif
        </div>
    </div>

    @if (session()->has('status'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @error('order')
        <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Items</flux:heading>
                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->lines as $line)
                            <flux:table.row>
                                <flux:table.cell>{{ $line->title_snapshot }}</flux:table.cell>
                                <flux:table.cell>{{ $line->sku_snapshot ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $line->quantity }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                <div class="mt-4 space-y-1 border-t pt-4 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500">Subtotal</span><span>{{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Shipping</span><span>{{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span>{{ number_format($order->tax_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Total</span><span>{{ number_format($order->total_amount / 100, 2) }}</span></div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Fulfillments</flux:heading>
                @if ($order->fulfillments->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">No fulfillments yet.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($order->fulfillments as $fulfillment)
                            <div class="rounded border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                <div class="flex items-center justify-between">
                                    <div>Fulfillment #{{ $fulfillment->id }} <flux:badge size="sm">{{ $fulfillment->status }}</flux:badge></div>
                                    <div class="flex gap-2">
                                        @if ($fulfillment->status === 'pending')
                                            <flux:button size="sm" wire:click="markShipped({{ $fulfillment->id }})">Mark shipped</flux:button>
                                        @endif
                                        @if ($fulfillment->status === 'shipped')
                                            <flux:button size="sm" wire:click="markDelivered({{ $fulfillment->id }})">Mark delivered</flux:button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Customer</flux:heading>
                <div class="mt-3 space-y-1 text-sm">
                    <div>{{ $order->customer?->name ?? $order->email }}</div>
                    <div class="text-zinc-500">{{ $order->email }}</div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Payment</flux:heading>
                <div class="mt-3 space-y-1 text-sm">
                    <div>Method: {{ $order->payment_method->value }}</div>
                    <div>Status: {{ $order->financial_status->value }}</div>
                    @if ($order->refundedTotal() > 0)
                        <div>Refunded: {{ number_format($order->refundedTotal() / 100, 2) }} {{ $order->currency }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <flux:modal wire:model.self="showFulfillModal" name="fulfill-modal">
        <div class="space-y-4">
            <flux:heading size="lg">Create fulfillment</flux:heading>
            @error('fulfill')
                <div class="rounded border border-red-200 bg-red-50 p-2 text-sm text-red-700">{{ $message }}</div>
            @enderror
            <div class="space-y-2">
                @foreach ($order->lines as $line)
                    <flux:field>
                        <flux:label>{{ $line->title_snapshot }} (max {{ $line->quantity }})</flux:label>
                        <flux:input type="number" wire:model="fulfillLines.{{ $line->id }}" min="0" :max="$line->quantity" />
                    </flux:field>
                @endforeach
            </div>
            <flux:field>
                <flux:label>Tracking number</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showFulfillModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createFulfillment" variant="primary" data-test="submit-fulfillment">Create</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showRefundModal" name="refund-modal">
        <div class="space-y-4">
            <flux:heading size="lg">Refund order</flux:heading>
            @error('refund')
                <div class="rounded border border-red-200 bg-red-50 p-2 text-sm text-red-700">{{ $message }}</div>
            @enderror
            <flux:field>
                <flux:label>Amount (cents)</flux:label>
                <flux:input type="number" wire:model="refundAmount" min="1" :max="$order->refundableAmount()" />
            </flux:field>
            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:input wire:model="refundReason" />
            </flux:field>
            <flux:field>
                <flux:checkbox wire:model="refundRestock" label="Restock items" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRefundModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createRefund" variant="primary" data-test="submit-refund">Refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
