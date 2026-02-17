<div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Order heading --}}
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
                    @php
                        $financialColor = match($order->financial_status->value) {
                            'paid' => 'green',
                            'refunded' => 'yellow',
                            'partially_refunded' => 'yellow',
                            default => 'zinc',
                        };
                        $fulfillColor = match($order->fulfillment_status->value) {
                            'fulfilled' => 'green',
                            'partial' => 'yellow',
                            default => 'zinc',
                        };
                    @endphp
                    <flux:badge :color="$financialColor">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                    <flux:badge :color="$fulfillColor">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                </div>
                <flux:text class="mt-1 text-sm text-zinc-500">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-3">
                @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->financial_status === \App\Enums\FinancialStatus::Pending)
                    <flux:button variant="primary" wire:click="confirmPayment">Confirm payment</flux:button>
                @endif

                @if ($order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled && in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                    <flux:button x-on:click="$flux.modal('create-fulfillment').show()">Create fulfillment</flux:button>
                @endif

                @if (in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-refund').show()">Refund</flux:button>
                @endif
            </div>

            {{-- Fulfillment guard --}}
            @if (! in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]) && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled)
                <flux:callout variant="warning">
                    <strong>Cannot create fulfillment.</strong> Payment must be confirmed before items can be fulfilled.
                    Current financial status: <em>{{ $order->financial_status->value }}</em>.
                </flux:callout>
            @endif

            {{-- Timeline --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Timeline</flux:heading>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-zinc-400"></div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Order placed</p>
                            <p class="text-xs text-zinc-500">{{ $order->placed_at?->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>

                    @if ($order->financial_status === \App\Enums\FinancialStatus::Paid)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Payment received</p>
                                <p class="text-xs text-zinc-500">{{ $order->payments->first()?->captured_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    @foreach ($order->fulfillments as $fulfillment)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Fulfillment created</p>
                                <p class="text-xs text-zinc-500">{{ $fulfillment->created_at?->format('M j, Y g:i A') }}</p>
                            </div>
                        </div>
                        @if ($fulfillment->shipped_at)
                            <div class="flex gap-3">
                                <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Shipped</p>
                                    <p class="text-xs text-zinc-500">{{ $fulfillment->shipped_at->format('M j, Y g:i A') }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($fulfillment->delivered_at)
                            <div class="flex gap-3">
                                <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Delivered</p>
                                    <p class="text-xs text-zinc-500">{{ $fulfillment->delivered_at->format('M j, Y g:i A') }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Order lines --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Order items</flux:heading>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                            <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Qty</th>
                            <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Unit Price</th>
                            <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-2 text-zinc-900 dark:text-zinc-100">
                                    {{ $line->title_snapshot }}
                                    @if ($line->sku_snapshot)
                                        <span class="text-xs text-zinc-400">{{ $line->sku_snapshot }}</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ $line->quantity }}</td>
                                <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">${{ number_format($line->unit_price_amount / 100, 2) }}</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">${{ number_format($line->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Order summary --}}
                <div class="mt-4 space-y-1 text-right text-sm">
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                        <span class="text-zinc-900 dark:text-zinc-100">${{ number_format($order->subtotal_amount / 100, 2) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-end gap-8">
                            <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                            <span class="text-green-600">-${{ number_format($order->discount_amount / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                        <span class="text-zinc-900 dark:text-zinc-100">${{ number_format($order->shipping_amount / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                        <span class="text-zinc-900 dark:text-zinc-100">${{ number_format($order->tax_amount / 100, 2) }}</span>
                    </div>
                    <flux:separator class="my-2" />
                    <div class="flex justify-end gap-8 font-semibold">
                        <span class="text-zinc-900 dark:text-zinc-100">Total</span>
                        <span class="text-zinc-900 dark:text-zinc-100">${{ number_format($order->total_amount / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment details --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Payment</flux:heading>
                @php $payment = $order->payments->first(); @endphp
                @if ($payment)
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Method</span>
                            <span class="text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                            @php
                                $payColor = match($payment->status->value) {
                                    'captured' => 'green',
                                    'failed' => 'red',
                                    'refunded' => 'yellow',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge :color="$payColor" size="sm">{{ ucfirst($payment->status->value) }}</flux:badge>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Amount</span>
                            <span class="text-zinc-900 dark:text-zinc-100">${{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</span>
                        </div>
                        @if ($payment->provider_payment_id)
                            <div class="flex justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Reference</span>
                                <span class="truncate text-zinc-600 dark:text-zinc-400">{{ $payment->provider_payment_id }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <flux:text class="text-zinc-400">No payment recorded.</flux:text>
                @endif
            </div>

            {{-- Fulfillment cards --}}
            @foreach ($order->fulfillments as $fulfillment)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="md">Fulfillment #{{ $loop->iteration }}</flux:heading>
                        @php
                            $fColor = match($fulfillment->status->value) {
                                'shipped' => 'blue',
                                'delivered' => 'green',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge :color="$fColor" size="sm">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                    </div>
                    @if ($fulfillment->tracking_number)
                        <div class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}
                            @if ($fulfillment->tracking_url)
                                <a href="{{ $fulfillment->tracking_url }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Track</a>
                            @endif
                        </div>
                    @endif
                    <div class="flex gap-2">
                        @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                            <flux:button size="sm" wire:click="markAsShipped({{ $fulfillment->id }})">Mark as shipped</flux:button>
                        @endif
                        @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                            <flux:button size="sm" wire:click="markAsDelivered({{ $fulfillment->id }})">Mark as delivered</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Customer card --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="my-3" />
                @if ($order->customer)
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $order->customer->name }}</p>
                    <p class="text-sm text-zinc-500">{{ $order->customer->email }}</p>
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline dark:text-blue-400" wire:navigate>View customer</a>
                @else
                    <p class="text-sm text-zinc-500">Guest</p>
                    <p class="text-sm text-zinc-500">{{ $order->email }}</p>
                @endif
            </div>

            {{-- Shipping address --}}
            @if ($order->shipping_address_json)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md">Shipping address</flux:heading>
                    <flux:separator class="my-3" />
                    @php $addr = $order->shipping_address_json; @endphp
                    <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>{{ $addr['line1'] ?? '' }}</p>
                        @if (! empty($addr['line2']))<p>{{ $addr['line2'] }}</p>@endif
                        <p>{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Billing address --}}
            @if ($order->billing_address_json)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md">Billing address</flux:heading>
                    <flux:separator class="my-3" />
                    @php $bAddr = $order->billing_address_json; @endphp
                    <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>{{ $bAddr['line1'] ?? '' }}</p>
                        @if (! empty($bAddr['line2']))<p>{{ $bAddr['line2'] }}</p>@endif
                        <p>{{ $bAddr['city'] ?? '' }}, {{ $bAddr['state'] ?? '' }} {{ $bAddr['zip'] ?? '' }}</p>
                        <p>{{ $bAddr['country'] ?? '' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfillment modal --}}
    <flux:modal name="create-fulfillment" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Create fulfillment</flux:heading>
            @foreach ($order->lines as $line)
                @php
                    $fulfilledQty = $line->fulfillmentLines ? $line->fulfillmentLines->sum('quantity') : 0;
                    $remaining = $line->quantity - $fulfilledQty;
                @endphp
                @if ($remaining > 0)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $line->title_snapshot }} ({{ $remaining }} unfulfilled)</span>
                        <flux:input type="number" wire:model="fulfillmentLines.{{ $line->id }}" min="0" max="{{ $remaining }}" class="w-20" size="sm" />
                    </div>
                @endif
            @endforeach
            <flux:separator />
            <flux:input wire:model="trackingCompany" label="Tracking company" placeholder="UPS, FedEx, DHL..." />
            <flux:input wire:model="trackingNumber" label="Tracking number" />
            <flux:input wire:model="trackingUrl" label="Tracking URL" type="url" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('create-fulfillment').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund modal --}}
    <flux:modal name="create-refund" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Refund order</flux:heading>
            @foreach ($order->lines as $line)
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $line->title_snapshot }}</span>
                    <flux:input type="number" wire:model="refundLines.{{ $line->id }}" min="0" max="{{ $line->quantity }}" class="w-20" size="sm" />
                </div>
            @endforeach
            <flux:separator />
            <flux:input wire:model="refundAmount" label="Or enter custom amount" type="number" step="0.01" placeholder="0.00" />
            <flux:textarea wire:model="refundReason" label="Reason" rows="3" placeholder="Reason for refund..." />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('create-refund').close()">Cancel</flux:button>
                <flux:button variant="danger" wire:click="createRefund">Create refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
