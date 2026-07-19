<div class="space-y-6">
    {{-- Order heading + status badges (spec 03 §8) --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
        <flux:badge size="sm" :color="match ($order->financial_status) {
            \App\Enums\FinancialStatus::Paid => 'green',
            \App\Enums\FinancialStatus::PartiallyRefunded, \App\Enums\FinancialStatus::Refunded => 'yellow',
            \App\Enums\FinancialStatus::Voided => 'red',
            default => 'zinc',
        }">{{ Str::headline($order->financial_status->value) }}</flux:badge>
        <flux:badge size="sm" :color="match ($order->fulfillment_status) {
            \App\Enums\FulfillmentOrderStatus::Fulfilled => 'green',
            \App\Enums\FulfillmentOrderStatus::Partial => 'yellow',
            default => 'zinc',
        }">{{ Str::headline($order->fulfillment_status->value) }}</flux:badge>
        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>
    </div>

    {{-- Action buttons --}}
    <div class="flex flex-wrap items-center gap-2">
        @if ($this->canConfirmPayment())
            @can('update', $order)
                <flux:button variant="primary" wire:click="confirmPayment" wire:loading.attr="disabled" wire:target="confirmPayment">Confirm payment</flux:button>
            @endcan
        @endif

        @can('createFulfillment', $order)
            @if (array_sum($unfulfilled) > 0)
                <flux:button variant="primary" wire:click="openFulfillmentModal" :disabled="$this->fulfillmentGuardBlocks()">Create fulfillment</flux:button>
            @endif
        @endcan

        @if ($this->canRefund())
            @can('createRefund', $order)
                <flux:button variant="ghost" wire:click="openRefundModal">Refund</flux:button>
            @endcan
        @endif

        @if ($this->canCancel())
            @can('cancel', $order)
                <flux:button variant="danger" wire:click="openCancelModal">Cancel order</flux:button>
            @endcan
        @endif
    </div>

    {{-- Fulfillment guard callout (spec 03 §8, spec 05 §11.5) --}}
    @if ($this->fulfillmentGuardBlocks() && array_sum($unfulfilled) > 0)
        <flux:callout variant="warning">
            <flux:callout.heading>Cannot create fulfillment</flux:callout.heading>
            <flux:callout.text>Fulfillment cannot be created until payment is confirmed. Current financial status: <em>{{ Str::headline($order->financial_status->value) }}</em>.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Timeline --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Timeline</flux:heading>
                <flux:separator class="my-4" />
                <ol class="relative space-y-4 border-l border-zinc-200 dark:border-zinc-700">
                    @foreach ($timeline as $event)
                        <li class="ml-4" wire:key="timeline-{{ $loop->index }}">
                            <div class="absolute -left-1.5 mt-1.5 size-3 rounded-full border border-white bg-zinc-300 dark:border-zinc-900 dark:bg-zinc-600"></div>
                            <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $event['title'] }}</flux:text>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event['time']?->format('M j, Y g:i A') }}</flux:text>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Order lines --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Order lines</flux:heading>
                <flux:separator class="my-4" />
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                <th class="w-14 px-2 py-2"><span class="sr-only">Image</span></th>
                                <th class="px-4 py-2 font-medium">Product</th>
                                <th class="px-4 py-2 font-medium">Qty</th>
                                <th class="px-4 py-2 font-medium">Unit price</th>
                                <th class="px-4 py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($order->lines as $line)
                                <tr wire:key="line-{{ $line->id }}">
                                    <td class="px-2 py-3">
                                        @php($media = $line->product?->media->first())
                                        @if ($media !== null && $media->status === \App\Enums\MediaStatus::Ready)
                                            <img src="{{ $media->urlFor('thumbnail') }}" alt="" class="size-10 rounded object-cover" loading="lazy">
                                        @else
                                            <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon name="photo" class="size-5 text-zinc-400" />
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $line->title_snapshot }}</div>
                                        @if ($line->sku_snapshot !== null)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $line->sku_snapshot }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $line->quantity }}</td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ \App\Support\Money::format($line->unit_price_amount, $order->currency) }}</td>
                                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ \App\Support\Money::format($line->total_amount, $order->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Order summary --}}
                <div class="mt-4 space-y-1 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                            <span class="text-zinc-900 dark:text-zinc-100">-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::format($order->shipping_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::format($order->tax_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 text-base font-semibold">
                        <span class="text-zinc-900 dark:text-zinc-100">Total</span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $order->formattedTotal() }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment details --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Payment details</flux:heading>
                <flux:separator class="my-4" />
                <div class="space-y-4">
                    @forelse ($order->payments as $payment)
                        <div class="flex flex-wrap items-center justify-between gap-2" wire:key="payment-{{ $payment->id }}">
                            <div>
                                <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ match ($payment->method) {
                                        \App\Enums\PaymentMethod::CreditCard => 'Credit Card',
                                        \App\Enums\PaymentMethod::Paypal => 'PayPal',
                                        \App\Enums\PaymentMethod::BankTransfer => 'Bank Transfer',
                                    } }}
                                </flux:text>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ \App\Support\Money::format($payment->amount, $payment->currency) }}
                                    @if ($payment->provider_payment_id !== null)
                                        · Ref: {{ $payment->provider_payment_id }}
                                    @endif
                                    · {{ $payment->created_at?->format('M j, Y g:i A') }}
                                </flux:text>
                            </div>
                            <flux:badge size="sm" :color="match ($payment->status) {
                                \App\Enums\PaymentStatus::Captured => 'green',
                                \App\Enums\PaymentStatus::Failed => 'red',
                                \App\Enums\PaymentStatus::Refunded => 'yellow',
                                default => 'zinc',
                            }">{{ Str::headline($payment->status->value) }}</flux:badge>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500 dark:text-zinc-400">No payments recorded.</flux:text>
                    @endforelse
                </div>

                @if ($this->canConfirmPayment())
                    @can('update', $order)
                        <flux:button variant="primary" class="mt-4" wire:click="confirmPayment" wire:loading.attr="disabled" wire:target="confirmPayment">Confirm payment</flux:button>
                    @endcan
                @endif
            </div>

            {{-- Fulfillments --}}
            @if ($order->fulfillments->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($order->fulfillments as $fulfillment)
                        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900" wire:key="fulfillment-{{ $fulfillment->id }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <flux:heading size="md">Fulfillment #{{ $fulfillment->id }}</flux:heading>
                                    <flux:badge size="sm" :color="match ($fulfillment->status) {
                                        \App\Enums\FulfillmentShipmentStatus::Shipped => 'blue',
                                        \App\Enums\FulfillmentShipmentStatus::Delivered => 'green',
                                        default => 'zinc',
                                    }">{{ Str::headline($fulfillment->status->value) }}</flux:badge>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                                        @can('update', $fulfillment)
                                            <flux:button size="sm" variant="primary" wire:click="openShipModal({{ $fulfillment->id }})">Mark as shipped</flux:button>
                                        @endcan
                                    @elseif ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                                        @can('update', $fulfillment)
                                            <flux:button size="sm" variant="primary" wire:click="markAsDelivered({{ $fulfillment->id }})">Mark as delivered</flux:button>
                                        @endcan
                                    @endif
                                </div>
                            </div>

                            @if ($fulfillment->tracking_company !== null || $fulfillment->tracking_number !== null || $fulfillment->tracking_url !== null)
                                <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    Tracking:
                                    {{ $fulfillment->tracking_company ?? '—' }}
                                    {{ $fulfillment->tracking_number ?? '' }}
                                    @if ($fulfillment->tracking_url !== null)
                                        · <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">Track shipment</a>
                                    @endif
                                </flux:text>
                            @endif

                            <flux:separator class="my-4" />
                            <ul class="space-y-1 text-sm">
                                @foreach ($fulfillment->lines as $fulfillmentLine)
                                    <li class="flex justify-between" wire:key="fulfillment-line-{{ $fulfillmentLine->id }}">
                                        <span class="text-zinc-600 dark:text-zinc-300">{{ $fulfillmentLine->orderLine?->title_snapshot ?? 'Unknown item' }}</span>
                                        <span class="text-zinc-500 dark:text-zinc-400">× {{ $fulfillmentLine->quantity }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Refunds --}}
            @if ($order->refunds->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md">Refunds</flux:heading>
                    <flux:separator class="my-4" />
                    <div class="space-y-4">
                        @foreach ($order->refunds->sortByDesc('created_at') as $refund)
                            <div class="flex flex-wrap items-center justify-between gap-2" wire:key="refund-{{ $refund->id }}">
                                <div>
                                    <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::format($refund->amount, $order->currency) }}</flux:text>
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $refund->created_at?->format('M j, Y g:i A') }}
                                        @if ($refund->reason !== null)
                                            · {{ $refund->reason }}
                                        @endif
                                    </flux:text>
                                </div>
                                <flux:badge size="sm" :color="match ($refund->status) {
                                    \App\Enums\RefundStatus::Processed => 'green',
                                    \App\Enums\RefundStatus::Failed => 'red',
                                    default => 'zinc',
                                }">{{ Str::headline($refund->status->value) }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Customer card --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="my-4" />
                <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->customer?->name ?? 'Guest' }}</flux:text>
                @if ($order->email !== null)
                    <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $order->email }}</flux:text>
                @endif
                @if ($order->customer !== null)
                    <div class="mt-2">
                        <a href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate class="text-sm text-blue-600 hover:underline dark:text-blue-400">View customer</a>
                    </div>
                @endif
            </div>

            {{-- Shipping address --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Shipping address</flux:heading>
                <flux:separator class="my-4" />
                @php($address = $order->shipping_address_json ?? [])
                @if ($address === [])
                    <flux:text class="text-zinc-500 dark:text-zinc-400">No shipping address.</flux:text>
                @else
                    <address class="text-sm not-italic text-zinc-600 dark:text-zinc-300">
                        {{ trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')) }}<br>
                        @if (! empty($address['company'])){{ $address['company'] }}<br>@endif
                        {{ $address['address1'] ?? '' }}<br>
                        @if (! empty($address['address2'])){{ $address['address2'] }}<br>@endif
                        {{ $address['city'] ?? '' }}{{ ! empty($address['province']) ? ', '.$address['province'] : '' }} {{ $address['postal_code'] ?? '' }}<br>
                        {{ $address['country'] ?? '' }}
                    </address>
                @endif
            </div>

            {{-- Billing address --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Billing address</flux:heading>
                <flux:separator class="my-4" />
                @php($address = $order->billing_address_json ?? [])
                @if ($address === [])
                    <flux:text class="text-zinc-500 dark:text-zinc-400">No billing address.</flux:text>
                @else
                    <address class="text-sm not-italic text-zinc-600 dark:text-zinc-300">
                        {{ trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')) }}<br>
                        @if (! empty($address['company'])){{ $address['company'] }}<br>@endif
                        {{ $address['address1'] ?? '' }}<br>
                        @if (! empty($address['address2'])){{ $address['address2'] }}<br>@endif
                        {{ $address['city'] ?? '' }}{{ ! empty($address['province']) ? ', '.$address['province'] : '' }} {{ $address['postal_code'] ?? '' }}<br>
                        {{ $address['country'] ?? '' }}
                    </address>
                @endif
            </div>
        </div>
    </div>

    {{-- Fulfillment modal (spec 03 §8) --}}
    <flux:modal wire:model="showFulfillmentModal" name="create-fulfillment" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Create fulfillment</flux:heading>

            @if ($errors->isNotEmpty())
                <flux:callout variant="danger">
                    <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    @if (($unfulfilled[$line->id] ?? 0) > 0)
                        <div class="flex items-center justify-between gap-4" wire:key="fulfill-{{ $line->id }}">
                            <flux:text class="text-zinc-900 dark:text-zinc-100">
                                {{ $line->title_snapshot }}
                                <span class="text-zinc-500 dark:text-zinc-400">({{ $unfulfilled[$line->id] }} unfulfilled)</span>
                            </flux:text>
                            <flux:input type="number" min="0" max="{{ $unfulfilled[$line->id] }}" wire:model.blur="fulfillmentLines.{{ $line->id }}" class="w-24" aria-label="Quantity to fulfill for {{ $line->title_snapshot }}" />
                        </div>
                    @endif
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label for="trackingCompany">Tracking company</flux:label>
                <flux:input id="trackingCompany" wire:model.blur="trackingCompany" placeholder="UPS, FedEx, DHL..." />
                <flux:error name="trackingCompany" />
            </flux:field>

            <flux:field>
                <flux:label for="trackingNumber">Tracking number</flux:label>
                <flux:input id="trackingNumber" wire:model.blur="trackingNumber" />
                <flux:error name="trackingNumber" />
            </flux:field>

            <flux:field>
                <flux:label for="trackingUrl">Tracking URL</flux:label>
                <flux:input id="trackingUrl" type="url" wire:model.blur="trackingUrl" placeholder="https://" />
                <flux:error name="trackingUrl" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment" wire:loading.attr="disabled" wire:target="createFulfillment">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Mark-as-shipped tracking modal --}}
    <flux:modal wire:model="showShipModal" name="ship-fulfillment" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Mark as shipped</flux:heading>

            @if ($errors->isNotEmpty())
                <flux:callout variant="danger">
                    <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
                </flux:callout>
            @endif

            <flux:field>
                <flux:label for="shipTrackingCompany">Tracking company</flux:label>
                <flux:input id="shipTrackingCompany" wire:model.blur="trackingCompany" placeholder="UPS, FedEx, DHL..." />
                <flux:error name="trackingCompany" />
            </flux:field>

            <flux:field>
                <flux:label for="shipTrackingNumber">Tracking number</flux:label>
                <flux:input id="shipTrackingNumber" wire:model.blur="trackingNumber" />
                <flux:error name="trackingNumber" />
            </flux:field>

            <flux:field>
                <flux:label for="shipTrackingUrl">Tracking URL</flux:label>
                <flux:input id="shipTrackingUrl" type="url" wire:model.blur="trackingUrl" placeholder="https://" />
                <flux:error name="trackingUrl" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showShipModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="markAsShipped" wire:loading.attr="disabled" wire:target="markAsShipped">Mark as shipped</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund modal (spec 03 §8) --}}
    <flux:modal wire:model="showRefundModal" name="create-refund" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Refund order</flux:heading>

            <flux:field>
                <flux:label for="refundAmount">Amount (cents)</flux:label>
                <flux:input id="refundAmount" type="number" min="1" max="{{ $refundableAmount }}" wire:model.blur="refundAmount" placeholder="0" />
                <flux:description>Refundable: {{ \App\Support\Money::format($refundableAmount, $order->currency) }}</flux:description>
                <flux:error name="refundAmount" />
            </flux:field>

            <flux:field>
                <flux:label for="refundReason">Reason</flux:label>
                <flux:textarea id="refundReason" rows="3" wire:model.blur="refundReason" placeholder="Reason for refund..." />
                <flux:error name="refundReason" />
            </flux:field>

            <flux:checkbox wire:model.blur="refundRestock" label="Restock returned items" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="createRefund" wire:loading.attr="disabled" wire:target="createRefund">Create refund</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel order modal --}}
    <flux:modal wire:model="showCancelModal" name="cancel-order" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Cancel this order?</flux:heading>
            <flux:text>Reserved inventory will be released and any pending payment will be voided. This cannot be undone.</flux:text>

            <flux:field>
                <flux:label for="cancelReason">Reason</flux:label>
                <flux:textarea id="cancelReason" rows="3" wire:model.blur="cancelReason" placeholder="Reason for cancellation..." />
                <flux:error name="cancelReason" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showCancelModal', false)">Keep order</flux:button>
                <flux:button variant="danger" wire:click="cancelOrder" wire:loading.attr="disabled" wire:target="cancelOrder">Cancel order</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
