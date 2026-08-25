<div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Heading --}}
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
                <flux:badge :color="$order->financial_status === 'paid' || $order->financial_status === 'partially_refunded' || $order->financial_status === 'refunded' ? 'green' : 'zinc'" size="sm">
                    {{ str_replace('_', ' ', ucfirst($order->financial_status)) }}
                </flux:badge>
                <flux:badge :color="$order->fulfillment_status === 'fulfilled' ? 'green' : ($order->fulfillment_status === 'partial' ? 'yellow' : 'zinc')" size="sm">
                    {{ ucfirst($order->fulfillment_status) }}
                </flux:badge>
            </div>
            <flux:text>{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($order->payment_method === 'bank_transfer' && $order->financial_status === 'pending')
                    <flux:button variant="primary" wire:click="confirmPayment" wire:loading.attr="disabled">
                        Confirm Payment
                    </flux:button>
                @endif

                @if (! $this->isFullyFulfilled && $this->canFulfill)
                    <flux:button variant="primary" wire:click="openFulfillmentModal">
                        Create fulfillment
                    </flux:button>
                @elseif (! $this->isFullyFulfilled)
                    <flux:button variant="primary" disabled>Create fulfillment</flux:button>
                @endif

                @if (in_array($order->financial_status, ['paid', 'partially_refunded'], true))
                    <flux:button variant="ghost" wire:click="openRefundModal">Refund</flux:button>
                @endif
            </div>

            {{-- Fulfillment guard --}}
            @if (! $this->canFulfill)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <b>Cannot create fulfillment.</b> Payment must be confirmed before items can be fulfilled.
                    Current financial status: {{ $order->financial_status }}.
                </flux:callout>
            @endif

            {{-- Timeline --}}
            <flux:card class="p-6">
                <flux:heading size="md">Timeline</flux:heading>

                <div class="mt-4 border-s border-zinc-200 ps-5 dark:border-zinc-700">
                    @foreach ($this->timeline as $index => $event)
                        <div class="relative pb-5 last:pb-0">
                            <span class="absolute -start-[25px] top-1 flex size-3 items-center justify-center rounded-full border border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-900">
                                <span class="size-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                            </span>
                            <p class="text-sm font-medium text-zinc-800 dark:text-white">{{ $event['title'] }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $event['time']?->format('M j, Y g:i A') }}</p>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            {{-- Fulfillment cards --}}
            @forelse ($order->fulfillments as $fulfillment)
                <flux:card class="p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:heading size="md">Fulfillment #{{ $fulfillment->id }}</flux:heading>
                        @php
                            $statusColors = ['pending' => 'zinc', 'shipped' => 'blue', 'delivered' => 'green'];
                        @endphp
                        <flux:badge :color="$statusColors[$fulfillment->status] ?? 'zinc'" size="sm">
                            {{ ucfirst($fulfillment->status) }}
                        </flux:badge>
                        <flux:spacer />
                        @if ($fulfillment->status === 'pending')
                            <flux:button variant="subtle" size="sm" wire:click="markAsShipped({{ $fulfillment->id }})">
                                Mark as shipped
                            </flux:button>
                        @elseif ($fulfillment->status === 'shipped')
                            <flux:button variant="subtle" size="sm" wire:click="markAsDelivered({{ $fulfillment->id }})">
                                Mark as delivered
                            </flux:button>
                        @endif
                    </div>

                    @if ($fulfillment->tracking_number)
                        <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-300">
                            @if ($fulfillment->tracking_company)
                                {{ $fulfillment->tracking_company }} —
                            @endif
                            {{ $fulfillment->tracking_number }}
                            @if ($fulfillment->tracking_url)
                                <a href="{{ $fulfillment->tracking_url }}" target="_blank" class="text-zinc-800 underline dark:text-white">Track</a>
                            @endif
                        </div>
                    @endif

                    <ul class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ($fulfillment->lines as $fulfillmentLine)
                            <li>
                                {{ $fulfillmentLine->quantity }} × {{ $fulfillmentLine->orderLine?->title_snapshot }}
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @empty
                <flux:card class="p-6">
                    <flux:text>No fulfillments yet.</flux:text>
                </flux:card>
            @endforelse

            {{-- Order lines --}}
            <flux:card class="p-6">
                <flux:heading size="md">Order lines</flux:heading>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                <th class="py-2 pe-3 text-start font-medium"></th>
                                <th class="py-2 pe-3 text-start font-medium">Product</th>
                                <th class="py-2 pe-3 text-end font-medium">Qty</th>
                                <th class="py-2 pe-3 text-end font-medium">Unit Price</th>
                                <th class="py-2 text-end font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->lines as $line)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="py-2.5 pe-3">
                                        @php
                                            $media = $line->variant?->product?->media()->orderBy('position')->first();
                                        @endphp
                                        @if ($media)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->storage_key) }}"
                                                alt=""
                                                class="size-10 rounded-lg object-cover"
                                            />
                                        @else
                                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                                <flux:icon.photo class="size-5" />
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pe-3">
                                        <div class="font-medium text-zinc-800 dark:text-white">{{ $line->title_snapshot }}</div>
                                        <div class="text-xs text-zinc-400">{{ $line->sku_snapshot }}</div>
                                    </td>
                                    <td class="py-2.5 pe-3 text-end">{{ $line->quantity }}</td>
                                    <td class="py-2.5 pe-3 text-end">{{ $this->formatMoney($line->unit_price_amount) }}</td>
                                    <td class="py-2.5 text-end font-medium text-zinc-800 dark:text-white">{{ $this->formatMoney($line->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Summary --}}
                <div class="mt-4 space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <flux:text>Subtotal</flux:text>
                        <span class="text-zinc-800 dark:text-white">{{ $this->formatMoney($order->subtotal_amount) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <flux:text>Discount</flux:text>
                            <span class="text-red-500">-{{ $this->formatMoney($order->discount_amount) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <flux:text>Shipping</flux:text>
                        <span class="text-zinc-800 dark:text-white">{{ $this->formatMoney($order->shipping_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Tax</flux:text>
                        <span class="text-zinc-800 dark:text-white">{{ $this->formatMoney($order->tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700">
                        <span>Total</span>
                        <span class="text-zinc-900 dark:text-white">{{ $this->formatMoney($order->total_amount) }}</span>
                    </div>
                </div>
            </flux:card>

            {{-- Payment details --}}
            <flux:card class="p-6">
                <flux:heading size="md">Payment details</flux:heading>

                @php
                    $payment = $order->payments->first();
                    $methodLabels = ['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'];
                    $paymentColors = ['pending' => 'zinc', 'captured' => 'green', 'failed' => 'red', 'refunded' => 'yellow'];
                @endphp

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text>Method</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Status</flux:text>
                        <flux:badge :color="$paymentColors[$payment?->status ?? 'pending'] ?? 'zinc'" size="sm">
                            {{ $payment?->status ?? 'pending' }}
                        </flux:badge>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Amount</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $this->formatMoney($order->total_amount) }}</span>
                    </div>
                    @if ($payment?->provider_payment_id)
                        <div class="flex justify-between">
                            <flux:text>Reference</flux:text>
                            <span class="font-mono text-xs text-zinc-500 dark:text-zinc-300">{{ $payment->provider_payment_id }}</span>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            <flux:card class="p-6">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="mt-3" />
                <div class="mt-4">
                    <p class="font-medium text-zinc-800 dark:text-white">{{ $order->customer?->name ?? 'Guest' }}</p>
                    <flux:text>{{ $order->email }}</flux:text>
                    @if ($order->customer)
                        <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="route('admin.customers.show', $order->customer)" wire:navigate class="mt-2">
                            View customer
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            <flux:card class="p-6">
                <flux:heading size="md">Shipping address</flux:heading>
                <flux:separator class="mt-3" />
                <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                    @php $address = $order->shipping_address_json ?? []; @endphp
                    @if ($address === [])
                        <flux:text>No shipping address.</flux:text>
                    @else
                        <p>{{ $address['line1'] ?? '' }}</p>
                        @if (! empty($address['line2']))
                            <p>{{ $address['line2'] }}</p>
                        @endif
                        <p>{{ trim(($address['city'] ?? '').' '.($address['state'] ?? '').' '.($address['zip'] ?? '')) }}</p>
                        <p>{{ $address['country'] ?? '' }}</p>
                    @endif
                </div>
            </flux:card>

            <flux:card class="p-6">
                <flux:heading size="md">Billing address</flux:heading>
                <flux:separator class="mt-3" />
                <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                    @php $billing = $order->billing_address_json ?? []; @endphp
                    @if ($billing === [])
                        <flux:text>No billing address.</flux:text>
                    @else
                        <p>{{ $billing['line1'] ?? '' }}</p>
                        @if (! empty($billing['line2']))
                            <p>{{ $billing['line2'] }}</p>
                        @endif
                        <p>{{ trim(($billing['city'] ?? '').' '.($billing['state'] ?? '').' '.($billing['zip'] ?? '')) }}</p>
                        <p>{{ $billing['country'] ?? '' }}</p>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Fulfillment modal --}}
    <flux:modal wire:model="showFulfillmentModal" class="max-w-lg">
        <flux:heading size="lg">Create fulfillment</flux:heading>

        <div class="mt-4 space-y-3">
            @foreach ($fulfillmentLines as $index => $row)
                @php
                    $line = $order->lines->firstWhere('id', $row['line_id']);
                    $max = $this->unfulfilledQuantities[$line?->id] ?? 0;
                @endphp
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-white">{{ $line?->title_snapshot }}</p>
                        <p class="text-xs text-zinc-400">{{ $max }} unfulfilled</p>
                    </div>
                    <flux:input
                        wire:model="fulfillmentLines.{{ $index }}.quantity"
                        type="number"
                        min="0"
                        max="{{ $max }}"
                        class="w-24"
                    />
                </div>
            @endforeach
        </div>

        <flux:separator class="my-4" />

        <div class="space-y-3">
            <flux:field>
                <flux:label>Tracking company</flux:label>
                <flux:input wire:model="trackingCompany" placeholder="UPS, FedEx, DHL..." />
            </flux:field>
            <flux:field>
                <flux:label>Tracking number</flux:label>
                <flux:input wire:model="trackingNumber" placeholder="1Z999AA10123456784" />
            </flux:field>
            <flux:field>
                <flux:label>Tracking URL</flux:label>
                <flux:input type="url" wire:model="trackingUrl" placeholder="https://..." />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createFulfillment" wire:loading.attr="disabled">Create fulfillment</flux:button>
        </div>
    </flux:modal>

    {{-- Refund modal --}}
    <flux:modal wire:model="showRefundModal" class="max-w-lg">
        <flux:heading size="lg">Refund order</flux:heading>

        <div class="mt-4 space-y-3">
            @foreach ($refundLines as $index => $row)
                @php $line = $order->lines->firstWhere('id', $row['line_id']); @endphp
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <flux:checkbox wire:model="refundLines.{{ $index }}.selected" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-white">{{ $line?->title_snapshot }}</p>
                        <p class="text-xs text-zinc-400">{{ $this->formatMoney($line?->unit_price_amount ?? 0) }} each</p>
                    </div>
                    <flux:input
                        wire:model="refundLines.{{ $index }}.quantity"
                        type="number"
                        min="0"
                        max="{{ $line?->quantity }}"
                        class="w-24"
                    />
                </div>
            @endforeach
        </div>

        <flux:separator class="my-4" />

        <div class="space-y-3">
            <flux:field>
                <flux:label>Or enter custom amount</flux:label>
                <flux:input wire:model="refundAmount" type="number" step="0.01" min="0" placeholder="0.00" />
            </flux:field>
            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" placeholder="Reason for refund..." />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="createRefund" wire:loading.attr="disabled">Create refund</flux:button>
        </div>
    </flux:modal>
</div>
