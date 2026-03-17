<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.orders.index') }}" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>#{{ $order->order_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Order Heading --}}
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl">#{{ $order->order_number }}</flux:heading>
                    <flux:badge :color="match($order->financial_status->value) {
                        'paid' => 'green',
                        'refunded' => 'yellow',
                        'voided' => 'red',
                        default => 'zinc',
                    }" size="sm">
                        {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                    </flux:badge>
                    <flux:badge :color="match($order->fulfillment_status->value) {
                        'fulfilled' => 'green',
                        'partial' => 'yellow',
                        default => 'zinc',
                    }" size="sm">
                        {{ ucfirst($order->fulfillment_status->value) }}
                    </flux:badge>
                </div>
                <flux:text class="mt-1 text-sm text-gray-500">
                    {{ $order->placed_at?->format('M j, Y g:i A') }}
                </flux:text>
            </div>

            {{-- Fulfillment Guard --}}
            @if (! in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                <flux:callout variant="warning">
                    <strong>Cannot create fulfillment.</strong> Payment must be confirmed before items can be fulfilled.
                    Current financial status: <em>{{ $order->financial_status->value }}</em>.
                </flux:callout>
            @endif

            {{-- Action Buttons --}}
            <div class="flex gap-3">
                @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->financial_status === \App\Enums\FinancialStatus::Pending)
                    <flux:button variant="primary" wire:click="confirmPayment">Confirm payment</flux:button>
                @endif
                @if (in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]) && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled)
                    <flux:button wire:click="openFulfillmentModal">Create fulfillment</flux:button>
                @endif
                @if (in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                    <flux:button variant="ghost" wire:click="openRefundModal">Refund</flux:button>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Timeline</flux:heading>
                <div class="space-y-4 border-l-2 border-gray-200 pl-6 dark:border-gray-700">
                    <div class="relative">
                        <div class="absolute -left-8 top-1 h-3 w-3 rounded-full bg-blue-500"></div>
                        <p class="text-sm font-medium">Order placed</p>
                        <p class="text-xs text-gray-500">{{ $order->placed_at?->format('M j, Y g:i A') }}</p>
                    </div>
                    @foreach ($order->payments as $payment)
                        @if ($payment->status === \App\Enums\PaymentStatus::Captured)
                            <div class="relative">
                                <div class="absolute -left-8 top-1 h-3 w-3 rounded-full bg-green-500"></div>
                                <p class="text-sm font-medium">Payment received</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                        @endif
                    @endforeach
                    @foreach ($order->fulfillments as $fulfillment)
                        <div class="relative">
                            <div class="absolute -left-8 top-1 h-3 w-3 rounded-full bg-purple-500"></div>
                            <p class="text-sm font-medium">Fulfillment created</p>
                            <p class="text-xs text-gray-500">{{ $fulfillment->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endforeach
                    @foreach ($order->refunds as $refund)
                        <div class="relative">
                            <div class="absolute -left-8 top-1 h-3 w-3 rounded-full bg-yellow-500"></div>
                            <p class="text-sm font-medium">Refund issued - ${{ number_format($refund->amount / 100, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ $refund->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Order Lines --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Order lines</flux:heading>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-medium text-gray-500">Product</th>
                            <th class="pb-2 text-right font-medium text-gray-500">Qty</th>
                            <th class="pb-2 text-right font-medium text-gray-500">Unit Price</th>
                            <th class="pb-2 text-right font-medium text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="line-{{ $line->id }}">
                                <td class="py-2">
                                    <p class="font-medium">{{ $line->title }}</p>
                                    @if ($line->sku)
                                        <p class="text-xs text-gray-500">{{ $line->sku }}</p>
                                    @endif
                                </td>
                                <td class="py-2 text-right">{{ $line->quantity }}</td>
                                <td class="py-2 text-right">${{ number_format($line->unit_price / 100, 2) }}</td>
                                <td class="py-2 text-right">${{ number_format($line->line_total / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Summary --}}
                <div class="mt-4 flex flex-col items-end gap-1 text-sm">
                    <div class="flex w-48 justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span>${{ number_format($order->subtotal_amount / 100, 2) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex w-48 justify-between">
                            <span class="text-gray-500">Discount</span>
                            <span class="text-red-500">-${{ number_format($order->discount_amount / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex w-48 justify-between">
                        <span class="text-gray-500">Shipping</span>
                        <span>${{ number_format($order->shipping_amount / 100, 2) }}</span>
                    </div>
                    <div class="flex w-48 justify-between">
                        <span class="text-gray-500">Tax</span>
                        <span>${{ number_format($order->tax_amount / 100, 2) }}</span>
                    </div>
                    <flux:separator class="my-1 w-48" />
                    <div class="flex w-48 justify-between font-bold">
                        <span>Total</span>
                        <span>${{ number_format($order->total_amount / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Details --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Payment details</flux:heading>
                @foreach ($order->payments as $payment)
                    <div class="mb-3 flex items-center gap-3" wire:key="payment-{{ $payment->id }}">
                        <flux:text>{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</flux:text>
                        <flux:badge :color="match($payment->status->value) {
                            'captured' => 'green',
                            'failed' => 'red',
                            'refunded' => 'yellow',
                            default => 'zinc',
                        }" size="sm">
                            {{ ucfirst($payment->status->value) }}
                        </flux:badge>
                    </div>
                    <flux:text class="text-sm text-gray-500">Amount: ${{ number_format($payment->amount / 100, 2) }}</flux:text>
                    @if ($payment->provider_payment_id)
                        <flux:text class="text-sm text-gray-500">Ref: {{ $payment->provider_payment_id }}</flux:text>
                    @endif
                @endforeach
            </div>

            {{-- Fulfillment Cards --}}
            @foreach ($order->fulfillments as $fulfillment)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900" wire:key="fulfillment-{{ $fulfillment->id }}">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <flux:heading size="md">Fulfillment</flux:heading>
                            <flux:badge :color="match($fulfillment->status->value) {
                                'shipped' => 'blue',
                                'delivered' => 'green',
                                default => 'zinc',
                            }" size="sm">
                                {{ ucfirst($fulfillment->status->value) }}
                            </flux:badge>
                        </div>
                        <div class="flex gap-2">
                            @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                                <flux:button size="sm" wire:click="markAsShipped({{ $fulfillment->id }})">Mark as shipped</flux:button>
                            @endif
                            @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                                <flux:button size="sm" wire:click="markAsDelivered({{ $fulfillment->id }})">Mark as delivered</flux:button>
                            @endif
                        </div>
                    </div>
                    @if ($fulfillment->tracking_number)
                        <flux:text class="text-sm text-gray-500">
                            {{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}
                            @if ($fulfillment->tracking_url)
                                <a href="{{ $fulfillment->tracking_url }}" target="_blank" class="text-blue-600 hover:underline">Track</a>
                            @endif
                        </flux:text>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Customer Card --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="my-3" />
                @if ($order->customer)
                    <p class="font-medium">{{ $order->customer->first_name }} {{ $order->customer->last_name }}</p>
                    <p class="text-sm text-gray-500">{{ $order->customer->email }}</p>
                    <a href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate class="mt-2 inline-block text-sm text-blue-600 hover:underline">View customer</a>
                @else
                    <flux:text class="text-gray-500">Guest</flux:text>
                    <p class="text-sm text-gray-500">{{ $order->email }}</p>
                @endif
            </div>

            {{-- Shipping Address --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md">Shipping address</flux:heading>
                <flux:separator class="my-3" />
                @if ($order->shipping_address_json)
                    @php $addr = $order->shipping_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $addr['line1'] ?? '' }}</p>
                        @if (!empty($addr['line2']))<p>{{ $addr['line2'] }}</p>@endif
                        <p>{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? '' }}</p>
                    </div>
                @else
                    <flux:text class="text-gray-500">No shipping address</flux:text>
                @endif
            </div>

            {{-- Billing Address --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md">Billing address</flux:heading>
                <flux:separator class="my-3" />
                @if ($order->billing_address_json)
                    @php $addr = $order->billing_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $addr['line1'] ?? '' }}</p>
                        @if (!empty($addr['line2']))<p>{{ $addr['line2'] }}</p>@endif
                        <p>{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? '' }}</p>
                    </div>
                @else
                    <flux:text class="text-gray-500">No billing address</flux:text>
                @endif
            </div>
        </div>
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal name="create-fulfillment" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Create fulfillment</flux:heading>
            @foreach ($order->lines as $lIndex => $line)
                <div class="flex items-center gap-3" wire:key="fl-{{ $line->id }}">
                    <flux:checkbox wire:model="fulfillmentLines.{{ $lIndex }}.selected" />
                    <span class="flex-1 text-sm">{{ $line->title }} ({{ $line->quantity }} unfulfilled)</span>
                    <flux:input wire:model="fulfillmentLines.{{ $lIndex }}.quantity" type="number" min="1" :max="$line->quantity" size="sm" class="w-20" />
                </div>
            @endforeach
            <flux:separator />
            <flux:field>
                <flux:label>Tracking company</flux:label>
                <flux:input wire:model="trackingCompany" placeholder="UPS, FedEx, DHL..." />
            </flux:field>
            <flux:field>
                <flux:label>Tracking number</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <flux:field>
                <flux:label>Tracking URL</flux:label>
                <flux:input wire:model="trackingUrl" type="url" />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('create-fulfillment').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal name="create-refund" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Refund order</flux:heading>
            @foreach ($order->lines as $rIndex => $line)
                <div class="flex items-center gap-3" wire:key="rl-{{ $line->id }}">
                    <flux:checkbox wire:model="refundLines.{{ $rIndex }}.selected" />
                    <span class="flex-1 text-sm">{{ $line->title }}</span>
                    <flux:input wire:model="refundLines.{{ $rIndex }}.quantity" type="number" min="0" :max="$line->quantity" size="sm" class="w-20" />
                </div>
            @endforeach
            <flux:separator />
            <flux:field>
                <flux:label>Or enter custom amount</flux:label>
                <flux:input wire:model="refundAmount" type="number" step="0.01" placeholder="0.00" />
            </flux:field>
            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" placeholder="Reason for refund..." />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('create-refund').close()">Cancel</flux:button>
                <flux:button variant="danger" wire:click="createRefund">Create refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
