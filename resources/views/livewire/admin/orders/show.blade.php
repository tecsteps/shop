<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
            <p class="text-sm text-neutral-500">{{ optional($order->placed_at)->format('M j, Y g:i A') }}</p>
        </div>
        <flux:button variant="ghost" href="{{ url('/admin/orders') }}">Back to orders</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:badge size="sm">Financial: {{ str_replace('_', ' ', $order->financial_status->value) }}</flux:badge>
        <flux:badge size="sm">Fulfillment: {{ $order->fulfillment_status->value }}</flux:badge>
        <flux:badge size="sm">Payment: {{ $order->payment_method->value }}</flux:badge>
    </div>

    @error('payment') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('fulfillment') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('refund') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('cancel') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror

    <div class="flex flex-wrap gap-2">
        @if ($order->payment_method->value === 'bank_transfer' && $order->financial_status->value === 'pending')
            <flux:button variant="primary" wire:click="confirmPayment">Confirm payment</flux:button>
        @endif
        @if ($canFulfill && ! in_array($order->financial_status->value, ['pending','voided']) && $order->fulfillment_status !== $fulfilled)
            <flux:button variant="primary" wire:click="openFulfillmentModal">Create fulfillment</flux:button>
        @endif
        @if ($canRefund && in_array($order->financial_status->value, ['paid','partially_refunded']))
            <flux:button variant="ghost" wire:click="openRefundModal">Refund</flux:button>
        @endif
        @if ($canCancel && $order->status->value !== 'cancelled' && $order->fulfillment_status !== $fulfilled)
            <flux:button variant="danger" wire:click="cancelOrder" wire:confirm="Cancel this order?">Cancel order</flux:button>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                    <flux:heading size="md">Order lines</flux:heading>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-neutral-500">
                        <tr>
                            <th class="px-4 py-2">Item</th>
                            <th class="px-4 py-2">Qty</th>
                            <th class="px-4 py-2">Unit</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            <tr wire:key="line-{{ $line->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ $line->title_snapshot }}</div>
                                    @if ($line->sku_snapshot)
                                        <div class="text-xs text-neutral-500">SKU: {{ $line->sku_snapshot }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $line->quantity }}</td>
                                <td class="px-4 py-2">{{ number_format($line->unit_price_amount / 100, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($line->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="space-y-1 border-t border-neutral-200 px-4 py-3 text-sm dark:border-neutral-800">
                    <div class="flex justify-between"><span class="text-neutral-500">Subtotal</span><span>{{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                    @if ($order->discount_amount)
                        <div class="flex justify-between"><span class="text-neutral-500">Discount</span><span>-{{ number_format($order->discount_amount / 100, 2) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-neutral-500">Shipping</span><span>{{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Tax</span><span>{{ number_format($order->tax_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Total</span><span>{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</span></div>
                </div>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                    <flux:heading size="md">Payments</flux:heading>
                </div>
                @if ($order->payments->isEmpty())
                    <p class="px-4 py-3 text-sm text-neutral-500">No payments recorded.</p>
                @else
                    <ul class="divide-y divide-neutral-100 text-sm dark:divide-neutral-800">
                        @foreach ($order->payments as $payment)
                            <li wire:key="payment-{{ $payment->id }}" class="flex items-center justify-between px-4 py-3">
                                <div>
                                    <div class="font-medium">{{ $payment->method }} - {{ $payment->status->value }}</div>
                                    <div class="text-xs text-neutral-500">{{ $payment->provider_payment_id }}</div>
                                </div>
                                <div>{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                    <flux:heading size="md">Fulfillments</flux:heading>
                </div>
                @if ($order->fulfillments->isEmpty())
                    <p class="px-4 py-3 text-sm text-neutral-500">No fulfillments yet.</p>
                @else
                    <ul class="divide-y divide-neutral-100 text-sm dark:divide-neutral-800">
                        @foreach ($order->fulfillments as $fulfillment)
                            <li wire:key="fulfillment-{{ $fulfillment->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                <div>
                                    <flux:badge size="sm">{{ $fulfillment->status->value }}</flux:badge>
                                    @if ($fulfillment->tracking_number)
                                        <span class="text-xs text-neutral-500">{{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}</span>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    @if ($fulfillment->status === $shipmentPending)
                                        <flux:button size="sm" variant="ghost" wire:click="markAsShipped({{ $fulfillment->id }})">Mark shipped</flux:button>
                                    @endif
                                    @if ($fulfillment->status === $shipmentShipped)
                                        <flux:button size="sm" variant="ghost" wire:click="markAsDelivered({{ $fulfillment->id }})">Mark delivered</flux:button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="my-2" />
                @if ($order->customer)
                    <div class="text-sm font-medium">{{ $order->customer->name ?? $order->customer->email }}</div>
                @else
                    <div class="text-sm font-medium">Guest</div>
                @endif
                <div class="text-sm text-neutral-500">{{ $order->email }}</div>
            </div>

            @if ($order->shipping_address_json)
                <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <flux:heading size="md">Shipping address</flux:heading>
                    <flux:separator class="my-2" />
                    <div class="space-y-1 text-sm">
                        <div>{{ $order->shipping_address_json['line1'] ?? '' }}</div>
                        <div>{{ $order->shipping_address_json['city'] ?? '' }} {{ $order->shipping_address_json['region'] ?? '' }} {{ $order->shipping_address_json['postal_code'] ?? '' }}</div>
                        <div>{{ $order->shipping_address_json['country'] ?? '' }}</div>
                    </div>
                </div>
            @endif

            @if ($order->billing_address_json)
                <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <flux:heading size="md">Billing address</flux:heading>
                    <flux:separator class="my-2" />
                    <div class="space-y-1 text-sm">
                        <div>{{ $order->billing_address_json['line1'] ?? '' }}</div>
                        <div>{{ $order->billing_address_json['city'] ?? '' }} {{ $order->billing_address_json['region'] ?? '' }} {{ $order->billing_address_json['postal_code'] ?? '' }}</div>
                        <div>{{ $order->billing_address_json['country'] ?? '' }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <flux:modal wire:model="showFulfillmentModal" name="create-fulfillment">
        <div class="space-y-4">
            <flux:heading size="lg">Create fulfillment</flux:heading>
            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    <div wire:key="ff-line-{{ $line->id }}" class="flex items-center justify-between gap-3">
                        <div class="text-sm">
                            <div class="font-medium">{{ $line->title_snapshot }}</div>
                            <div class="text-xs text-neutral-500">Unfulfilled: {{ $line->unfulfilledQuantity() }}</div>
                        </div>
                        <flux:input type="number" wire:model="fulfillmentLineQuantities.{{ $line->id }}" min="0" max="{{ $line->unfulfilledQuantity() }}" class="w-24" />
                    </div>
                @endforeach
            </div>
            <flux:separator />
            <flux:field>
                <flux:label>Tracking company</flux:label>
                <flux:input wire:model="trackingCompany" />
            </flux:field>
            <flux:field>
                <flux:label>Tracking number</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <flux:field>
                <flux:label>Tracking URL</flux:label>
                <flux:input wire:model="trackingUrl" type="url" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRefundModal" name="create-refund">
        <div class="space-y-4">
            <flux:heading size="lg">Refund order</flux:heading>
            <flux:field>
                <flux:label>Amount (cents)</flux:label>
                <flux:input type="number" wire:model="refundAmount" min="1" max="{{ $order->remainingRefundable() }}" />
                <flux:description>Maximum refundable: {{ number_format($order->remainingRefundable() / 100, 2) }} {{ $order->currency }}</flux:description>
            </flux:field>
            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="createRefund">Create refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
