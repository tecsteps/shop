<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.orders.index') }}" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-4 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
                    <flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : 'zinc'">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
                    <flux:badge :color="$order->fulfillment_status->value === 'fulfilled' ? 'green' : 'zinc'">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                </div>
                <flux:text variant="subtle" class="mt-1">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($showConfirmPayment)
                        @can('confirmPayment', $order)
                            <flux:button variant="primary" wire:click="confirmPayment">Confirm payment</flux:button>
                        @endcan
                    @endif
                    @if ($order->fulfillment_status->value !== 'fulfilled')
                        @can('createFulfillment', $order)
                            <flux:button variant="primary" wire:click="openFulfillmentModal" :disabled="! $canFulfill">Create fulfillment</flux:button>
                        @endcan
                    @endif
                    @if ($showRefund)
                        @can('createRefund', $order)
                            <flux:button variant="ghost" wire:click="openRefundModal">Refund</flux:button>
                        @endcan
                    @endif
                </div>

                @if (! $canFulfill && $order->fulfillment_status->value !== 'fulfilled')
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="Cannot create fulfillment." class="mt-4">
                        Payment must be confirmed before items can be fulfilled. Current financial status: {{ $order->financial_status->value }}.
                    </flux:callout>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Timeline</flux:heading>
                <ol class="mt-4 border-l border-zinc-200 dark:border-zinc-700">
                    <li class="relative pb-4 pl-4">
                        <span class="absolute -left-1 top-1 size-2 rounded-full bg-zinc-900 dark:bg-white"></span>
                        <p class="text-sm font-semibold">Order placed</p>
                        <p class="text-xs text-zinc-500">{{ $order->placed_at?->format('M j, Y g:i A') }}</p>
                    </li>
                    @foreach ($order->payments->filter(fn ($p) => $p->status->value === 'captured') as $paymentEvent)
                        <li class="relative pb-4 pl-4" wire:key="timeline-payment-{{ $paymentEvent->getKey() }}">
                            <span class="absolute -left-1 top-1 size-2 rounded-full bg-zinc-900 dark:bg-white"></span>
                            <p class="text-sm font-semibold">Payment received</p>
                            <p class="text-xs text-zinc-500">{{ $this->money($paymentEvent->amount, $paymentEvent->currency) }} via {{ str_replace('_', ' ', $paymentEvent->method->value) }}</p>
                        </li>
                    @endforeach
                    @foreach ($order->fulfillments as $timelineFulfillment)
                        <li class="relative pb-4 pl-4" wire:key="timeline-fulfillment-{{ $timelineFulfillment->getKey() }}">
                            <span class="absolute -left-1 top-1 size-2 rounded-full bg-zinc-900 dark:bg-white"></span>
                            <p class="text-sm font-semibold">Fulfillment {{ $timelineFulfillment->status->value }}</p>
                            <p class="text-xs text-zinc-500">{{ $timelineFulfillment->shipped_at?->format('M j, Y g:i A') ?? 'Pending' }}</p>
                        </li>
                    @endforeach
                    @foreach ($order->refunds as $timelineRefund)
                        <li class="relative pb-4 pl-4" wire:key="timeline-refund-{{ $timelineRefund->getKey() }}">
                            <span class="absolute -left-1 top-1 size-2 rounded-full bg-zinc-900 dark:bg-white"></span>
                            <p class="text-sm font-semibold">Refund {{ $timelineRefund->status->value }}</p>
                            <p class="text-xs text-zinc-500">{{ $this->money($timelineRefund->amount, $order->currency) }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Order lines</flux:heading>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                <th class="pb-2 font-medium"><span class="sr-only">Image</span></th>
                                <th class="pb-2 font-medium">Product</th>
                                <th class="pb-2 font-medium">Qty</th>
                                <th class="pb-2 font-medium">Unit Price</th>
                                <th class="pb-2 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->orderLines as $line)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="line-{{ $line->getKey() }}">
                                    <td class="py-2 pr-2">
                                        @if ($line->variant?->product?->productMedia->first()?->storage_key)
                                            <img src="{{ Storage::disk('public')->url($line->variant->product->productMedia->first()->storage_key) }}" alt="" class="size-10 rounded object-cover" />
                                        @else
                                            <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon.photo class="size-5 text-zinc-400" variant="outline" />
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-2">{{ $line->title_snapshot }}<br /><span class="text-xs text-zinc-500">{{ $line->sku_snapshot }}</span></td>
                                    <td class="py-2 pr-2">{{ $line->quantity }}</td>
                                    <td class="py-2 pr-2">{{ $this->money($line->unit_price_amount, $order->currency) }}</td>
                                    <td class="py-2">{{ $this->money($line->total_amount, $order->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col gap-1 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ $this->money($order->subtotal_amount, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Discount</span><span>-{{ $this->money($order->discount_amount, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Shipping</span><span>{{ $this->money($order->shipping_amount, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ $this->money($order->tax_amount, $order->currency) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Total</span><span>{{ $this->money($order->total_amount, $order->currency) }}</span></div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Payment details</flux:heading>
                @if ($payment)
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span>{{ ucwords(str_replace('_', ' ', $order->payment_method->value)) }}</span>
                            <flux:badge :color="match ($payment->status->value) { 'captured' => 'green', 'failed' => 'red', 'refunded' => 'yellow', default => 'zinc' }">{{ ucfirst($payment->status->value) }}</flux:badge>
                        </div>
                        <p>Amount: {{ $this->money($payment->amount, $payment->currency) }}</p>
                        <p class="break-all text-xs text-zinc-500">Ref: {{ $payment->provider_payment_id }}</p>
                        @if ($showConfirmPayment)
                            @can('confirmPayment', $order)
                                <div><flux:button variant="primary" size="sm" wire:click="confirmPayment" class="mt-2">Confirm payment</flux:button></div>
                            @endcan
                        @endif
                    </div>
                @else
                    <flux:text variant="subtle" class="mt-3">No payment recorded.</flux:text>
                @endif
            </div>

            @foreach ($order->fulfillments as $fulfillment)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900" wire:key="fulfillment-{{ $fulfillment->getKey() }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:badge :color="match ($fulfillment->status->value) { 'delivered' => 'green', 'shipped' => 'blue', default => 'zinc' }">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                        <div class="flex gap-2">
                            @if ($fulfillment->status->value === 'pending')
                                <flux:button variant="ghost" size="sm" wire:click="markAsShipped({{ $fulfillment->getKey() }})">Mark as shipped</flux:button>
                            @elseif ($fulfillment->status->value === 'shipped')
                                <flux:button variant="ghost" size="sm" wire:click="markAsDelivered({{ $fulfillment->getKey() }})">Mark as delivered</flux:button>
                            @endif
                        </div>
                    </div>
                    @if ($fulfillment->tracking_company || $fulfillment->tracking_number)
                        <p class="mt-2 text-sm">{{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}</p>
                        @if ($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-sm underline">Track shipment</a>
                        @endif
                    @endif
                    <ul class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($fulfillment->fulfillmentLines as $fulfillmentLine)
                            <li>{{ $fulfillmentLine->orderLine?->title_snapshot }} x {{ $fulfillmentLine->quantity }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Customer</flux:heading>
                <flux:separator class="my-3" />
                <p class="text-sm font-medium">{{ $order->customer?->name ?? 'Guest' }}</p>
                <p class="text-sm text-zinc-500">{{ $order->email }}</p>
                @if ($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="mt-2 inline-block text-sm underline" wire:navigate>View customer</a>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Shipping address</flux:heading>
                <flux:separator class="my-3" />
                @php $shipping = $order->shipping_address_json ?? []; @endphp
                <p class="text-sm">{{ $shipping['address1'] ?? '' }}</p>
                <p class="text-sm">{{ $shipping['city'] ?? '' }}{{ isset($shipping['province']) && $shipping['province'] !== '' ? ', '.$shipping['province'] : '' }} {{ $shipping['zip'] ?? $shipping['postal_code'] ?? '' }}</p>
                <p class="text-sm">{{ $shipping['country'] ?? '' }}</p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Billing address</flux:heading>
                <flux:separator class="my-3" />
                @php $billing = $order->billing_address_json ?? []; @endphp
                <p class="text-sm">{{ $billing['address1'] ?? '' }}</p>
                <p class="text-sm">{{ $billing['city'] ?? '' }}{{ isset($billing['province']) && $billing['province'] !== '' ? ', '.$billing['province'] : '' }} {{ $billing['zip'] ?? $billing['postal_code'] ?? '' }}</p>
                <p class="text-sm">{{ $billing['country'] ?? '' }}</p>
            </div>
        </div>
    </div>

    <flux:modal wire:model="showFulfillmentModal" class="max-w-lg">
        <flux:heading size="lg">Create fulfillment</flux:heading>
        <div class="mt-4 flex flex-col gap-3">
            @foreach ($order->orderLines as $line)
                @php $unfulfilled = $line->quantity - $line->fulfillmentLines->sum('quantity'); @endphp
                @if ($unfulfilled > 0)
                    <div class="flex items-center gap-2" wire:key="fulfill-line-{{ $line->getKey() }}">
                        <span class="flex-1 text-sm">{{ $line->title_snapshot }} ({{ $unfulfilled }} unfulfilled)</span>
                        <flux:input type="number" min="0" max="{{ $unfulfilled }}" wire:model="fulfillmentLines.{{ $line->getKey() }}" aria-label="Quantity to fulfill" class="w-24" />
                    </div>
                @endif
            @endforeach
        </div>
        <flux:separator class="my-4" />
        <flux:input wire:model="trackingCompany" label="Tracking company" placeholder="UPS, FedEx, DHL..." />
        <flux:input wire:model="trackingNumber" label="Tracking number" class="mt-3" />
        <flux:input wire:model="trackingUrl" label="Tracking URL" type="url" class="mt-3" />
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createFulfillment">Create fulfillment</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRefundModal" class="max-w-lg">
        <flux:heading size="lg">Refund order</flux:heading>
        <div class="mt-4 flex flex-col gap-3">
            @foreach ($order->orderLines as $line)
                <div class="flex items-center gap-2" wire:key="refund-line-{{ $line->getKey() }}">
                    <flux:checkbox wire:model="refundSelected.{{ $line->getKey() }}" aria-label="Refund {{ $line->title_snapshot }}" />
                    <span class="flex-1 text-sm">{{ $line->title_snapshot }}</span>
                    <flux:input type="number" min="0" max="{{ $line->quantity }}" wire:model="refundLines.{{ $line->getKey() }}" aria-label="Quantity to refund" class="w-24" />
                </div>
            @endforeach
        </div>
        <flux:separator class="my-4" />
        <flux:input wire:model="refundAmount" label="Or enter custom amount" type="number" step="0.01" min="0" placeholder="0.00" />
        <flux:textarea wire:model="refundReason" label="Reason" rows="3" placeholder="Reason for refund..." class="mt-3" />
        <label class="mt-3 flex items-center gap-2 text-sm">
            <flux:checkbox wire:model="restock" />
            <span>Restock refunded items</span>
        </label>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="createRefund">Create refund</flux:button>
        </div>
    </flux:modal>
</div>
