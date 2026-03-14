<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.orders.index') }}" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>#{{ $order->order_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order header --}}
            <div>
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <flux:heading size="xl">#{{ $order->order_number }}</flux:heading>
                    @php
                        $financialColor = match($order->financial_status) {
                            \App\Enums\FinancialStatus::Paid => 'green',
                            \App\Enums\FinancialStatus::Refunded => 'yellow',
                            \App\Enums\FinancialStatus::PartiallyRefunded => 'yellow',
                            \App\Enums\FinancialStatus::Voided => 'red',
                            default => 'zinc',
                        };
                        $fulfillColor = match($order->fulfillment_status) {
                            \App\Enums\FulfillmentStatus::Fulfilled => 'green',
                            \App\Enums\FulfillmentStatus::Partial => 'yellow',
                            default => 'zinc',
                        };
                    @endphp
                    <flux:badge color="{{ $financialColor }}">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                    <flux:badge color="{{ $fulfillColor }}">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                </div>
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>
            </div>

            {{-- Fulfillment guard callout --}}
            @if (!in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]) && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled)
                <flux:callout variant="warning">
                    <strong>Cannot create fulfillment.</strong> Payment must be confirmed before items can be fulfilled.
                    Current financial status: <em>{{ str_replace('_', ' ', $order->financial_status->value) }}</em>.
                </flux:callout>
            @endif

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-2">
                @if ($this->canConfirmPayment())
                    <flux:button wire:click="confirmPayment" variant="primary" wire:confirm="Are you sure you want to confirm this payment?">
                        Confirm Payment
                    </flux:button>
                @endif

                @if ($this->canCreateFulfillment())
                    <flux:button wire:click="openFulfillmentModal">
                        Create Fulfillment
                    </flux:button>
                @endif

                @if ($this->canRefund())
                    <flux:button wire:click="openRefundModal" variant="ghost">
                        Refund
                    </flux:button>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Timeline</flux:heading>
                <flux:separator class="mb-4" />

                <div class="relative pl-6 space-y-4">
                    <div class="absolute top-2 left-[7px] bottom-2 w-px bg-zinc-200 dark:bg-zinc-700"></div>
                    @foreach ($this->timeline as $event)
                        <div class="relative flex items-start gap-3">
                            <div class="absolute -left-6 top-1.5 size-3 rounded-full bg-zinc-300 dark:bg-zinc-600 ring-2 ring-white dark:ring-zinc-800"></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $event['title'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event['time'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Order lines --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Order Lines</flux:heading>
                <flux:separator class="mb-4" />

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                                <th class="text-left px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                                <th class="text-center px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Qty</th>
                                <th class="text-right px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Unit Price</th>
                                <th class="text-right px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($order->lines as $line)
                                <tr wire:key="line-{{ $line->id }}">
                                    <td class="px-2 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($line->product && $line->product->media->first())
                                                <img src="{{ Storage::url($line->product->media->first()->path) }}" alt="" class="size-10 rounded object-cover" />
                                            @else
                                                <div class="size-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                                    <flux:icon name="cube" class="size-5 text-zinc-400" />
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                                                @if ($line->variant_title_snapshot)
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant_title_snapshot }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3 text-zinc-600 dark:text-zinc-400">{{ $line->sku_snapshot ?? '-' }}</td>
                                    <td class="px-2 py-3 text-center text-zinc-900 dark:text-white">{{ $line->quantity }}</td>
                                    <td class="px-2 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($line->unit_price_amount / 100, 2) }}</td>
                                    <td class="px-2 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ number_format($line->total_amount / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Order totals --}}
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex flex-col items-end gap-1 text-sm">
                        <div class="flex justify-between w-48">
                            <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                            <span class="text-zinc-900 dark:text-white">{{ number_format($order->subtotal_amount / 100, 2) }}</span>
                        </div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between w-48">
                                <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                                <span class="text-red-600 dark:text-red-400">-{{ number_format($order->discount_amount / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between w-48">
                            <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                            <span class="text-zinc-900 dark:text-white">{{ number_format($order->shipping_amount / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between w-48">
                            <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                            <span class="text-zinc-900 dark:text-white">{{ number_format($order->tax_amount / 100, 2) }}</span>
                        </div>
                        <flux:separator class="w-48 my-1" />
                        <div class="flex justify-between w-48 font-bold">
                            <span class="text-zinc-900 dark:text-white">Total</span>
                            <span class="text-zinc-900 dark:text-white">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency ?? 'EUR' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Payment Details</flux:heading>
                <flux:separator class="mb-4" />

                @foreach ($order->payments as $payment)
                    <div wire:key="payment-{{ $payment->id }}" class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400">Method:</span>
                            <span class="text-zinc-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</span>
                            @php
                                $paymentColor = match($payment->status) {
                                    \App\Enums\PaymentStatus::Captured => 'green',
                                    \App\Enums\PaymentStatus::Failed => 'red',
                                    \App\Enums\PaymentStatus::Refunded => 'yellow',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge color="{{ $paymentColor }}" size="sm">{{ ucfirst($payment->status->value) }}</flux:badge>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Amount:</span>
                            <span class="text-zinc-900 dark:text-white">{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency ?? $order->currency ?? 'EUR' }}</span>
                        </div>
                        @if ($payment->provider_payment_id)
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Ref:</span>
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $payment->provider_payment_id }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($this->canConfirmPayment())
                    <div class="mt-4">
                        <flux:button wire:click="confirmPayment" variant="primary" size="sm" wire:confirm="Are you sure you want to confirm this payment?">
                            Confirm Payment
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Fulfillments --}}
            @foreach ($order->fulfillments as $fulfillment)
                <div wire:key="fulfillment-{{ $fulfillment->id }}" class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="md">Fulfillment #{{ $loop->iteration }}</flux:heading>
                        @php
                            $shipColor = match($fulfillment->status) {
                                \App\Enums\FulfillmentShipmentStatus::Shipped => 'blue',
                                \App\Enums\FulfillmentShipmentStatus::Delivered => 'green',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge color="{{ $shipColor }}">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                    </div>
                    <flux:separator class="mb-4" />

                    @if ($fulfillment->tracking_company || $fulfillment->tracking_number)
                        <div class="mb-4 text-sm space-y-1">
                            @if ($fulfillment->tracking_company)
                                <p><span class="text-zinc-500 dark:text-zinc-400">Carrier:</span> <span class="text-zinc-900 dark:text-white">{{ $fulfillment->tracking_company }}</span></p>
                            @endif
                            @if ($fulfillment->tracking_number)
                                <p><span class="text-zinc-500 dark:text-zinc-400">Tracking:</span> <span class="font-mono text-zinc-900 dark:text-white">{{ $fulfillment->tracking_number }}</span></p>
                            @endif
                            @if ($fulfillment->tracking_url)
                                <p><a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Track shipment</a></p>
                            @endif
                        </div>
                    @endif

                    <div class="text-sm space-y-1 mb-4">
                        @foreach ($fulfillment->lines as $fLine)
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>{{ $fLine->orderLine->title_snapshot ?? 'Unknown' }}</span>
                                <span>x{{ $fLine->quantity }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex gap-2">
                        @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                            <flux:button wire:click="markAsShipped({{ $fulfillment->id }})" size="sm">Mark as Shipped</flux:button>
                        @endif
                        @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                            <flux:button wire:click="markAsDelivered({{ $fulfillment->id }})" size="sm">Mark as Delivered</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Refunds --}}
            @if ($order->refunds->isNotEmpty())
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-4">Refunds</flux:heading>
                    <flux:separator class="mb-4" />

                    <div class="space-y-3">
                        @foreach ($order->refunds as $refund)
                            <div wire:key="refund-{{ $refund->id }}" class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ number_format($refund->amount / 100, 2) }} {{ $order->currency ?? 'EUR' }}</p>
                                    @if ($refund->reason)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $refund->reason }}</p>
                                    @endif
                                    <p class="text-xs text-zinc-400">{{ $refund->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                                <flux:badge color="{{ $refund->status->value === 'processed' ? 'green' : 'red' }}" size="sm">
                                    {{ ucfirst($refund->status->value) }}
                                </flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column (1/3) --}}
        <div class="space-y-6">
            {{-- Customer card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-3">Customer</flux:heading>
                <flux:separator class="mb-3" />

                @if ($order->customer)
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $order->customer->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->customer->email }}</p>
                    <a href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate class="inline-block mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        View customer
                    </a>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Guest</p>
                    @if ($order->email)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->email }}</p>
                    @endif
                @endif
            </div>

            {{-- Shipping address --}}
            @if ($order->shipping_address_json)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-3">Shipping Address</flux:heading>
                    <flux:separator class="mb-3" />

                    @php $shipping = $order->shipping_address_json; @endphp
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-0.5">
                        @if (!empty($shipping['name']))
                            <p>{{ $shipping['name'] }}</p>
                        @endif
                        @if (!empty($shipping['address1']))
                            <p>{{ $shipping['address1'] }}</p>
                        @endif
                        @if (!empty($shipping['address2']))
                            <p>{{ $shipping['address2'] }}</p>
                        @endif
                        <p>
                            {{ $shipping['city'] ?? '' }}{{ !empty($shipping['province']) ? ', ' . $shipping['province'] : '' }}
                            {{ $shipping['postal_code'] ?? '' }}
                        </p>
                        @if (!empty($shipping['country_code']))
                            <p>{{ $shipping['country_code'] }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Billing address --}}
            @if ($order->billing_address_json)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-3">Billing Address</flux:heading>
                    <flux:separator class="mb-3" />

                    @php $billing = $order->billing_address_json; @endphp
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-0.5">
                        @if (!empty($billing['name']))
                            <p>{{ $billing['name'] }}</p>
                        @endif
                        @if (!empty($billing['address1']))
                            <p>{{ $billing['address1'] }}</p>
                        @endif
                        @if (!empty($billing['address2']))
                            <p>{{ $billing['address2'] }}</p>
                        @endif
                        <p>
                            {{ $billing['city'] ?? '' }}{{ !empty($billing['province']) ? ', ' . $billing['province'] : '' }}
                            {{ $billing['postal_code'] ?? '' }}
                        </p>
                        @if (!empty($billing['country_code']))
                            <p>{{ $billing['country_code'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfillment modal --}}
    <flux:modal name="create-fulfillment" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Create Fulfillment</flux:heading>

            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    @php
                        $fulfilledQty = $line->fulfillmentLines->sum('quantity');
                        $unfulfilled = $line->quantity - $fulfilledQty;
                    @endphp
                    @if ($unfulfilled > 0)
                        <div wire:key="fulfill-line-{{ $line->id }}" class="flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $unfulfilled }} unfulfilled</p>
                            </div>
                            <flux:input
                                type="number"
                                wire:model="fulfillmentLines.{{ $line->id }}"
                                min="0"
                                max="{{ $unfulfilled }}"
                                class="w-20"
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            <flux:separator />

            <flux:input wire:model="trackingCompany" label="Tracking Company" placeholder="UPS, FedEx, DHL..." />
            <flux:input wire:model="trackingNumber" label="Tracking Number" />
            <flux:input wire:model="trackingUrl" label="Tracking URL" type="url" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" @click="$dispatch('close-modal', { name: 'create-fulfillment' })">Cancel</flux:button>
                <flux:button wire:click="createFulfillment" variant="primary">Create Fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund modal --}}
    <flux:modal name="create-refund" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Refund Order</flux:heading>

            <flux:field>
                <flux:label>Refund Amount</flux:label>
                <flux:input wire:model="refundAmount" type="number" step="0.01" min="0" placeholder="Full order amount if empty" />
                <flux:description>Leave empty to refund the full order amount ({{ number_format($order->total_amount / 100, 2) }} {{ $order->currency ?? 'EUR' }}).</flux:description>
            </flux:field>

            <flux:textarea wire:model="refundReason" label="Reason" rows="3" placeholder="Reason for refund..." />

            <flux:checkbox wire:model="refundRestock" label="Restock items" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" @click="$dispatch('close-modal', { name: 'create-refund' })">Cancel</flux:button>
                <flux:button wire:click="createRefund" variant="danger">Create Refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
