@php
    use App\Support\Storefront\PriceFormatter;

    $currency = $order->currency;
    $financialColors = ['pending' => 'zinc', 'paid' => 'green', 'refunded' => 'yellow', 'partially_refunded' => 'yellow', 'voided' => 'red'];
    $fulfillmentColors = ['unfulfilled' => 'zinc', 'partial' => 'yellow', 'fulfilled' => 'green'];
    $shipmentColors = ['pending' => 'zinc', 'shipped' => 'blue', 'delivered' => 'green'];
    $shipping = $order->shipping_address_json ?? [];
    $billing = $order->billing_address_json ?? [];
@endphp

<div>
    <x-admin.breadcrumbs :items="[
        ['label' => __('Orders'), 'href' => route('admin.orders.index')],
        ['label' => $order->order_number],
    ]" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column. --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Heading + actions. --}}
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" level="1">{{ $order->order_number }}</flux:heading>
                    <flux:badge :color="$financialColors[$order->financial_status->value] ?? 'zinc'">
                        {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                    </flux:badge>
                    <flux:badge :color="$fulfillmentColors[$order->fulfillment_status->value] ?? 'zinc'">
                        {{ ucfirst($order->fulfillment_status->value) }}
                    </flux:badge>
                </div>
                <flux:text class="mt-1">{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>

                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($this->canConfirmPayment)
                        <flux:button variant="primary" icon="check" wire:click="confirmPayment" data-test="confirm-payment">{{ __('Confirm payment') }}</flux:button>
                    @endif

                    @if ($this->canFulfill && $order->fulfillment_status->value !== 'fulfilled')
                        <flux:button variant="primary" icon="truck" wire:click="openFulfillmentModal" data-test="open-fulfillment">{{ __('Create fulfillment') }}</flux:button>
                    @endif

                    @if ($this->canRefund)
                        <flux:button variant="ghost" icon="arrow-uturn-left" wire:click="openRefundModal" data-test="open-refund">{{ __('Refund') }}</flux:button>
                    @endif
                </div>

                @unless ($this->canFulfill)
                    <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                        <flux:callout.heading>{{ __('Cannot create fulfillment') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('Payment must be confirmed before items can be fulfilled. Current financial status:') }}
                            <strong>{{ str_replace('_', ' ', $order->financial_status->value) }}</strong>.
                        </flux:callout.text>
                    </flux:callout>
                @endunless
            </div>

            {{-- Timeline. --}}
            <x-admin.card title="{{ __('Timeline') }}">
                <ol class="relative space-y-5 border-s border-zinc-200 ps-5 dark:border-zinc-700">
                    @foreach ($this->timeline as $event)
                        <li class="relative">
                            <span class="absolute -start-[1.45rem] top-1 size-2.5 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                            <flux:text class="font-medium">{{ $event['title'] }}</flux:text>
                            <flux:text class="text-xs">{{ $event['at']?->format('M j, Y g:i A') }}</flux:text>
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>

            {{-- Order lines. --}}
            <x-admin.card title="{{ __('Order lines') }}">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Product') }}</flux:table.column>
                        <flux:table.column class="text-center">{{ __('Qty') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('Unit') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->lines as $line)
                            <flux:table.row :key="'line-'.$line->id">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $line->title_snapshot }}</div>
                                    @if ($line->sku_snapshot)
                                        <div class="text-xs text-zinc-500">{{ $line->sku_snapshot }}</div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-center">{{ $line->quantity }}</flux:table.cell>
                                <flux:table.cell class="text-right">{{ PriceFormatter::format($line->unit_price_amount, $currency) }}</flux:table.cell>
                                <flux:table.cell class="text-right">{{ PriceFormatter::format($line->total_amount, $currency) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                {{-- Summary. --}}
                <div class="ms-auto mt-4 max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>{{ PriceFormatter::format($order->subtotal_amount, $currency) }}</span></div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between"><span>{{ __('Discount') }}</span><span>-{{ PriceFormatter::format($order->discount_amount, $currency) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span>{{ __('Shipping') }}</span><span>{{ PriceFormatter::format($order->shipping_amount, $currency) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Tax') }}</span><span>{{ PriceFormatter::format($order->tax_amount, $currency) }}</span></div>
                    <flux:separator class="my-1" />
                    <div class="flex justify-between font-semibold"><span>{{ __('Total') }}</span><span>{{ PriceFormatter::format($order->total_amount, $currency) }}</span></div>
                    @if ($order->refundedAmount() > 0)
                        <div class="flex justify-between text-yellow-600 dark:text-yellow-500"><span>{{ __('Refunded') }}</span><span>-{{ PriceFormatter::format($order->refundedAmount(), $currency) }}</span></div>
                    @endif
                </div>
            </x-admin.card>

            {{-- Payment details. --}}
            <x-admin.card title="{{ __('Payment details') }}">
                @foreach ($order->payments as $payment)
                    <div class="flex flex-wrap items-center gap-3 text-sm" wire:key="payment-{{ $payment->id }}">
                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</span>
                        <flux:badge size="sm" :color="['pending' => 'zinc', 'captured' => 'green', 'failed' => 'red', 'refunded' => 'yellow'][$payment->status->value] ?? 'zinc'">
                            {{ ucfirst($payment->status->value) }}
                        </flux:badge>
                        <span>{{ PriceFormatter::format($payment->amount, $payment->currency) }}</span>
                        @if ($payment->provider_payment_id)
                            <span class="text-xs text-zinc-500">{{ $payment->provider_payment_id }}</span>
                        @endif
                    </div>
                @endforeach

                @if ($this->canConfirmPayment)
                    <div class="mt-4">
                        <flux:button variant="primary" wire:click="confirmPayment">{{ __('Confirm payment') }}</flux:button>
                    </div>
                @endif
            </x-admin.card>

            {{-- Fulfillment cards. --}}
            @foreach ($order->fulfillments as $fulfillment)
                <x-admin.card wire:key="fulfillment-{{ $fulfillment->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <flux:heading size="md">{{ __('Fulfillment') }} #{{ $fulfillment->id }}</flux:heading>
                            <flux:badge size="sm" :color="$shipmentColors[$fulfillment->status->value] ?? 'zinc'">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                        </div>
                        <div class="flex gap-2">
                            @if ($fulfillment->status->value === 'pending')
                                <flux:button size="sm" variant="primary" wire:click="markAsShipped({{ $fulfillment->id }})">{{ __('Mark as shipped') }}</flux:button>
                            @elseif ($fulfillment->status->value === 'shipped')
                                <flux:button size="sm" variant="primary" wire:click="markAsDelivered({{ $fulfillment->id }})">{{ __('Mark as delivered') }}</flux:button>
                            @endif
                        </div>
                    </div>

                    @if ($fulfillment->tracking_company || $fulfillment->tracking_number)
                        <flux:text class="mt-2 text-sm">
                            {{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}
                            @if ($fulfillment->tracking_url)
                                <flux:link :href="$fulfillment->tracking_url" target="_blank">{{ __('Track') }}</flux:link>
                            @endif
                        </flux:text>
                    @endif

                    <ul class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($fulfillment->lines as $fline)
                            <li>{{ $fline->orderLine?->title_snapshot }} &times; {{ $fline->quantity }}</li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endforeach
        </div>

        {{-- Right column. --}}
        <div class="space-y-6">
            <x-admin.card title="{{ __('Customer') }}">
                <flux:separator class="mb-3" />
                <flux:text class="font-medium">{{ $order->customer?->name ?? __('Guest') }}</flux:text>
                <flux:text class="text-sm">{{ $order->email }}</flux:text>
                @if ($order->customer)
                    <flux:link :href="route('admin.customers.show', $order->customer)" wire:navigate class="mt-2 block text-sm">{{ __('View customer') }}</flux:link>
                @endif
            </x-admin.card>

            <x-admin.card title="{{ __('Shipping address') }}">
                <flux:separator class="mb-3" />
                @if (! empty($shipping))
                    <address class="text-sm not-italic text-zinc-600 dark:text-zinc-400">
                        {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br>
                        {{ $shipping['address1'] ?? '' }}<br>
                        @if (! empty($shipping['address2'])){{ $shipping['address2'] }}<br>@endif
                        {{ $shipping['city'] ?? '' }}{{ ! empty($shipping['province_code']) ? ', '.$shipping['province_code'] : '' }} {{ $shipping['postal_code'] ?? '' }}<br>
                        {{ $shipping['country'] ?? '' }}
                    </address>
                @else
                    <flux:text class="text-sm">{{ __('No shipping address.') }}</flux:text>
                @endif
            </x-admin.card>

            <x-admin.card title="{{ __('Billing address') }}">
                <flux:separator class="mb-3" />
                @if (! empty($billing))
                    <address class="text-sm not-italic text-zinc-600 dark:text-zinc-400">
                        {{ trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) }}<br>
                        {{ $billing['address1'] ?? '' }}<br>
                        {{ $billing['city'] ?? '' }}{{ ! empty($billing['province_code']) ? ', '.$billing['province_code'] : '' }} {{ $billing['postal_code'] ?? '' }}<br>
                        {{ $billing['country'] ?? '' }}
                    </address>
                @else
                    <flux:text class="text-sm">{{ __('Same as shipping.') }}</flux:text>
                @endif
            </x-admin.card>
        </div>
    </div>

    {{-- Fulfillment modal. --}}
    <flux:modal wire:model.self="showFulfillmentModal" name="create-fulfillment" class="md:w-[32rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Create fulfillment') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    @php($remaining = $line->quantity - $line->fulfilledQuantity())
                    @if ($remaining > 0)
                        <div class="flex items-center justify-between gap-3" wire:key="ful-line-{{ $line->id }}">
                            <div>
                                <div class="text-sm font-medium">{{ $line->title_snapshot }}</div>
                                <div class="text-xs text-zinc-500">{{ __(':n unfulfilled', ['n' => $remaining]) }}</div>
                            </div>
                            <flux:input type="number" min="0" :max="$remaining" wire:model="fulfillmentLines.{{ $line->id }}" class="w-20" size="sm" />
                        </div>
                    @endif
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label>{{ __('Tracking company') }}</flux:label>
                <flux:input wire:model="trackingCompany" placeholder="UPS, FedEx, DHL..." />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Tracking number') }}</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Tracking URL') }}</flux:label>
                <flux:input type="url" wire:model="trackingUrl" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment" data-test="submit-fulfillment">{{ __('Create fulfillment') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund modal. --}}
    <flux:modal wire:model.self="showRefundModal" name="create-refund" class="md:w-[32rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Refund order') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    <div class="flex items-center justify-between gap-3" wire:key="ref-line-{{ $line->id }}">
                        <div class="text-sm font-medium">{{ $line->title_snapshot }}</div>
                        <flux:input type="number" min="0" :max="$line->quantity" wire:model="refundLines.{{ $line->id }}" class="w-20" size="sm" />
                    </div>
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label>{{ __('Or enter custom amount') }}</flux:label>
                <flux:input type="number" step="0.01" wire:model="refundAmount" placeholder="0.00" data-test="refund-amount" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Reason') }}</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" placeholder="{{ __('Reason for refund...') }}" />
            </flux:field>

            <flux:checkbox wire:model="refundRestock" :label="__('Restock refunded items')" />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="createRefund" data-test="submit-refund">{{ __('Create refund') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
