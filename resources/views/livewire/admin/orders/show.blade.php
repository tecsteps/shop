<div>
    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.orders.index') }}">Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>#{{ $order->order_number }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page heading + actions --}}
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Order #{{ $order->order_number }}</flux:heading>

        <div class="flex items-center gap-2">
            @unless ($isFullyFulfilled || $isCancelled)
                <flux:button wire:click="openFulfillModal" variant="primary" size="sm">
                    Fulfill
                </flux:button>
            @endunless

            @unless ($isCancelled)
                <flux:button wire:click="openRefundModal" variant="ghost" size="sm">
                    Refund
                </flux:button>
                <flux:button wire:click="openCancelModal" variant="ghost" size="sm" class="text-red-600 hover:text-red-800 dark:text-red-400">
                    Cancel
                </flux:button>
            @endunless
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column: order details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order status --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Order status</flux:heading>
                <div class="flex flex-wrap gap-3">
                    @php
                        $statusColor = match($order->status) {
                            \App\Enums\OrderStatus::Paid => 'green',
                            \App\Enums\OrderStatus::Pending => 'yellow',
                            \App\Enums\OrderStatus::Fulfilled => 'blue',
                            \App\Enums\OrderStatus::Cancelled => 'red',
                            \App\Enums\OrderStatus::Refunded => 'red',
                            default => 'zinc',
                        };
                        $financialColor = match($order->financial_status) {
                            \App\Enums\FinancialStatus::Paid => 'green',
                            \App\Enums\FinancialStatus::Pending => 'yellow',
                            \App\Enums\FinancialStatus::Authorized => 'blue',
                            \App\Enums\FinancialStatus::PartiallyRefunded => 'orange',
                            \App\Enums\FinancialStatus::Refunded => 'red',
                            \App\Enums\FinancialStatus::Voided => 'zinc',
                            default => 'zinc',
                        };
                        $fulfillmentColor = match($order->fulfillment_status) {
                            \App\Enums\FulfillmentStatus::Fulfilled => 'green',
                            \App\Enums\FulfillmentStatus::Partial => 'yellow',
                            \App\Enums\FulfillmentStatus::Unfulfilled => 'zinc',
                            default => 'zinc',
                        };
                    @endphp
                    <flux:badge :color="$statusColor">Status: {{ ucfirst($order->status->value) }}</flux:badge>
                    <flux:badge :color="$financialColor">Financial: {{ str_replace('_', ' ', ucfirst($order->financial_status->value)) }}</flux:badge>
                    <flux:badge :color="$fulfillmentColor">Fulfillment: {{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                </div>
                <flux:text class="mt-3 text-zinc-500">Placed {{ $order->created_at->format('M d, Y \a\t H:i') }}</flux:text>

                @if ($order->cancelled_at)
                    <div class="mt-3">
                        <flux:text class="text-red-600 dark:text-red-400">
                            Cancelled {{ $order->cancelled_at->format('M d, Y \a\t H:i') }}
                            @if ($order->cancel_reason)
                                - {{ $order->cancel_reason }}
                            @endif
                        </flux:text>
                    </div>
                @endif
            </div>

            {{-- Order lines --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Order lines</flux:heading>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                                <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                                <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Qty</th>
                                <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Unit Price</th>
                                <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->lines as $line)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="py-2 text-zinc-900 dark:text-zinc-100">
                                        {{ $line->title_snapshot }}
                                        @if ($line->variant_title_snapshot)
                                            <span class="text-zinc-500 dark:text-zinc-400"> - {{ $line->variant_title_snapshot }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ $line->sku_snapshot }}</td>
                                    <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">{{ $line->quantity }}</td>
                                    <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ \App\Support\MoneyFormatter::format($line->unit_price_amount) }}</td>
                                    <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($line->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($order->subtotal_amount) }}</span>
                            </div>
                            @if ($order->discount_amount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                                    <span class="text-red-600 dark:text-red-400">-{{ \App\Support\MoneyFormatter::format($order->discount_amount) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($order->shipping_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($order->tax_amount) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-zinc-200 pt-1 font-medium dark:border-zinc-700">
                                <span class="text-zinc-900 dark:text-zinc-100">Total</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($order->total_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payments --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Payments</flux:heading>

                @forelse ($order->payments as $payment)
                    <div class="flex items-center justify-between border-b border-zinc-100 py-3 last:border-0 dark:border-zinc-800">
                        <div>
                            <flux:text class="font-medium">{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</flux:text>
                            <flux:text class="text-zinc-500">{{ $payment->reference }}</flux:text>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ \App\Support\MoneyFormatter::format($payment->amount) }}</span>
                            @php
                                $paymentColor = match($payment->status) {
                                    \App\Enums\PaymentStatus::Captured => 'green',
                                    \App\Enums\PaymentStatus::Pending => 'yellow',
                                    \App\Enums\PaymentStatus::Failed => 'red',
                                    \App\Enums\PaymentStatus::Refunded => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$paymentColor">{{ ucfirst($payment->status->value) }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">No payments recorded.</flux:text>
                @endforelse

                {{-- Refunds --}}
                @if ($order->refunds->isNotEmpty())
                    <flux:heading size="base" class="mt-6 mb-3">Refunds</flux:heading>
                    @foreach ($order->refunds as $refund)
                        <div class="flex items-center justify-between border-b border-zinc-100 py-3 last:border-0 dark:border-zinc-800">
                            <div>
                                <flux:text class="font-medium">{{ \App\Support\MoneyFormatter::format($refund->amount) }}</flux:text>
                                @if ($refund->reason)
                                    <flux:text class="text-zinc-500">{{ $refund->reason }}</flux:text>
                                @endif
                            </div>
                            @php
                                $refundColor = match($refund->status) {
                                    \App\Enums\RefundStatus::Processed => 'green',
                                    \App\Enums\RefundStatus::Pending => 'yellow',
                                    \App\Enums\RefundStatus::Failed => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$refundColor">{{ ucfirst($refund->status->value) }}</flux:badge>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Fulfillments --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Fulfillments</flux:heading>

                @forelse ($order->fulfillments as $fulfillment)
                    <div class="mb-4 rounded-lg border border-zinc-100 p-4 last:mb-0 dark:border-zinc-800">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                @php
                                    $shipmentColor = match($fulfillment->status) {
                                        \App\Enums\FulfillmentShipmentStatus::Shipped => 'blue',
                                        \App\Enums\FulfillmentShipmentStatus::Delivered => 'green',
                                        \App\Enums\FulfillmentShipmentStatus::Pending => 'yellow',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$shipmentColor">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                            </div>
                            <flux:text class="text-zinc-500">{{ $fulfillment->created_at->format('M d, Y') }}</flux:text>
                        </div>

                        @if ($fulfillment->tracking_number)
                            <div class="mb-2 text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Tracking:</span>
                                @if ($fulfillment->tracking_url)
                                    <a href="{{ $fulfillment->tracking_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        {{ $fulfillment->tracking_number }}
                                    </a>
                                @else
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $fulfillment->tracking_number }}</span>
                                @endif
                                @if ($fulfillment->tracking_company)
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $fulfillment->tracking_company }})</span>
                                @endif
                            </div>
                        @endif

                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            @foreach ($fulfillment->lines as $fLine)
                                <div>{{ $fLine->orderLine->title_snapshot }} x {{ $fLine->quantity }}</div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">No fulfillments yet.</flux:text>
                @endforelse
            </div>
        </div>

        {{-- Right column: customer + shipping --}}
        <div class="space-y-6">
            {{-- Customer info --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Customer</flux:heading>

                @if ($order->customer)
                    <div class="space-y-2">
                        <div>
                            <a href="{{ route('admin.customers.show', $order->customer) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                {{ $order->customer->full_name }}
                            </a>
                        </div>
                        <flux:text class="text-zinc-500">{{ $order->customer->email }}</flux:text>
                        @if ($order->customer->phone)
                            <flux:text class="text-zinc-500">{{ $order->customer->phone }}</flux:text>
                        @endif
                    </div>
                @else
                    <flux:text class="text-zinc-500">Guest checkout</flux:text>
                @endif
            </div>

            {{-- Shipping address --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Shipping address</flux:heading>

                @if ($order->shipping_address_json)
                    @php $address = $order->shipping_address_json; @endphp
                    <div class="space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                        <div>{{ $address['first_name'] ?? '' }} {{ $address['last_name'] ?? '' }}</div>
                        @if (!empty($address['company']))
                            <div>{{ $address['company'] }}</div>
                        @endif
                        <div>{{ $address['address1'] ?? '' }}</div>
                        @if (!empty($address['address2']))
                            <div>{{ $address['address2'] }}</div>
                        @endif
                        <div>{{ $address['zip'] ?? '' }} {{ $address['city'] ?? '' }}</div>
                        @if (!empty($address['province']))
                            <div>{{ $address['province'] }}</div>
                        @endif
                        <div>{{ $address['country'] ?? '' }}</div>
                        @if (!empty($address['phone']))
                            <div>{{ $address['phone'] }}</div>
                        @endif
                    </div>
                @else
                    <flux:text class="text-zinc-500">No shipping address.</flux:text>
                @endif
            </div>

            {{-- Billing address --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Billing address</flux:heading>

                @if ($order->billing_address_json)
                    @php $billing = $order->billing_address_json; @endphp
                    <div class="space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                        <div>{{ $billing['first_name'] ?? '' }} {{ $billing['last_name'] ?? '' }}</div>
                        @if (!empty($billing['company']))
                            <div>{{ $billing['company'] }}</div>
                        @endif
                        <div>{{ $billing['address1'] ?? '' }}</div>
                        @if (!empty($billing['address2']))
                            <div>{{ $billing['address2'] }}</div>
                        @endif
                        <div>{{ $billing['zip'] ?? '' }} {{ $billing['city'] ?? '' }}</div>
                        @if (!empty($billing['province']))
                            <div>{{ $billing['province'] }}</div>
                        @endif
                        <div>{{ $billing['country'] ?? '' }}</div>
                    </div>
                @else
                    <flux:text class="text-zinc-500">No billing address.</flux:text>
                @endif
            </div>

            {{-- Notes --}}
            @if ($order->notes)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                    <flux:text class="text-zinc-700 dark:text-zinc-300">{{ $order->notes }}</flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfill Modal --}}
    <flux:modal wire:model="showFulfillModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Create fulfillment</flux:heading>

            <div class="space-y-4">
                @foreach ($this->getUnfulfilledLines() as $uLine)
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <flux:text class="font-medium">{{ $uLine['title'] }}</flux:text>
                            @if ($uLine['variant_title'])
                                <flux:text class="text-zinc-500 text-sm">{{ $uLine['variant_title'] }}</flux:text>
                            @endif
                            <flux:text class="text-zinc-400 text-xs">{{ $uLine['unfulfilled'] }} of {{ $uLine['quantity'] }} remaining</flux:text>
                        </div>
                        <div class="w-20">
                            <flux:input type="number" min="0" max="{{ $uLine['unfulfilled'] }}" wire:model="fulfillLines.{{ $uLine['id'] }}" />
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label>Tracking number</flux:label>
                <flux:input wire:model="trackingNumber" placeholder="Optional" />
            </flux:field>

            <flux:field>
                <flux:label>Tracking URL</flux:label>
                <flux:input wire:model="trackingUrl" placeholder="Optional" />
            </flux:field>

            <flux:field>
                <flux:label>Carrier</flux:label>
                <flux:input wire:model="carrier" placeholder="e.g. DHL, UPS, FedEx" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeFulfillModal" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createFulfillment" variant="primary">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Issue refund</flux:heading>

            <flux:field>
                <flux:label>Refund amount (EUR)</flux:label>
                <flux:input type="number" step="0.01" min="0" wire:model="refundAmount" placeholder="Leave empty for line-based calculation" />
                <flux:description>
                    Maximum refundable: {{ \App\Support\MoneyFormatter::format($order->total_amount - $order->refunds->sum('amount')) }}
                </flux:description>
            </flux:field>

            <div class="space-y-3">
                <flux:label>Refund line items (optional)</flux:label>
                @foreach ($order->lines as $line)
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <flux:text class="font-medium text-sm">{{ $line->title_snapshot }}</flux:text>
                            <flux:text class="text-zinc-500 text-xs">{{ \App\Support\MoneyFormatter::format($line->unit_price_amount) }} each, qty {{ $line->quantity }}</flux:text>
                        </div>
                        <div class="w-20">
                            <flux:input type="number" min="0" max="{{ $line->quantity }}" wire:model="refundLines.{{ $line->id }}" />
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:textarea wire:model="refundReason" placeholder="Optional reason for refund" rows="2" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeRefundModal" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="processRefund" variant="primary">Process refund</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel Modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Cancel order</flux:heading>

            <flux:callout variant="warning">
                Are you sure you want to cancel this order? This action cannot be undone.
            </flux:callout>

            <flux:field>
                <flux:label>Reason (optional)</flux:label>
                <flux:textarea wire:model="cancelReason" placeholder="Why is this order being cancelled?" rows="2" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeCancelModal" variant="ghost">Keep order</flux:button>
                <flux:button wire:click="cancelOrder" variant="danger">Cancel order</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
