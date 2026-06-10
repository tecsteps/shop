@php
    use App\Enums\FinancialStatus;
    use App\Enums\FulfillmentShipmentStatus;
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Support\Storefront\PriceFormatter;

    $order = $this->order;
    $showConfirmPayment = $order->payment_method === PaymentMethod::BankTransfer
        && $order->financial_status === FinancialStatus::Pending;
    $canRefund = in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);
    $canCancel = $order->status !== OrderStatus::Cancelled
        && $order->fulfillment_status === \App\Enums\FulfillmentStatus::Unfulfilled;
@endphp

<div class="space-y-6">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Orders'), 'href' => route('admin.orders.index')],
        ['label' => $order->order_number],
    ]" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT COLUMN (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Heading --}}
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" level="1">{{ $order->order_number }}</flux:heading>
                    <x-admin.status-badge :status="$order->financial_status" data-test="financial-status-badge" />
                    <x-admin.status-badge :status="$order->fulfillment_status" data-test="fulfillment-status-badge" />
                </div>
                <flux:text>{{ $order->placed_at?->format('M j, Y g:i A') }}</flux:text>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($showConfirmPayment)
                    @can('update', $order)
                        <flux:button variant="primary" wire:click="confirmPayment" data-test="confirm-payment-button">
                            {{ __('Confirm payment') }}
                        </flux:button>
                    @endcan
                @endif

                @if ($this->canCreateFulfillment)
                    @can('createFulfillment', $order)
                        <flux:modal.trigger name="create-fulfillment">
                            <flux:button data-test="create-fulfillment-button">{{ __('Create fulfillment') }}</flux:button>
                        </flux:modal.trigger>
                    @endcan
                @endif

                @if ($canRefund)
                    @can('createRefund', $order)
                        <flux:modal.trigger name="create-refund">
                            <flux:button variant="ghost" data-test="refund-button">{{ __('Refund') }}</flux:button>
                        </flux:modal.trigger>
                    @endcan
                @endif

                @if ($canCancel)
                    @can('cancel', $order)
                        <flux:modal.trigger name="cancel-order">
                            <flux:button variant="ghost" class="!text-red-600 dark:!text-red-400" data-test="cancel-order-button">
                                {{ __('Cancel order') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endcan
                @endif
            </div>

            {{-- Fulfillment guard callout --}}
            @if (! $order->financial_status->allowsFulfillment() && $order->status !== OrderStatus::Cancelled && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled)
                <flux:callout variant="warning" icon="exclamation-triangle" data-test="fulfillment-guard-callout">
                    <flux:callout.heading>{{ __('Cannot create fulfillment.') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Payment must be confirmed before items can be fulfilled. Current financial status: :status.', ['status' => $order->financial_status->value]) }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Timeline --}}
            <x-admin.card :heading="__('Timeline')">
                <ol class="space-y-5 border-l border-zinc-200 pl-5 dark:border-zinc-700">
                    @foreach ($this->timeline as $event)
                        <li class="relative" wire:key="timeline-{{ $loop->index }}">
                            <span class="absolute top-1.5 -left-[26px] size-2.5 rounded-full bg-zinc-400 ring-4 ring-white dark:bg-zinc-500 dark:ring-zinc-900"></span>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $event['label'] }}</p>
                            @if ($event['description'] !== null)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event['description'] }}</p>
                            @endif
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $event['timestamp']->format('M j, Y g:i A') }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>

            {{-- Fulfillment cards --}}
            @foreach ($order->fulfillments as $fulfillment)
                <x-admin.card wire:key="fulfillment-{{ $fulfillment->id }}" data-test="fulfillment-card-{{ $fulfillment->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <flux:heading>{{ __('Fulfillment #:id', ['id' => $fulfillment->id]) }}</flux:heading>
                            <x-admin.status-badge :status="$fulfillment->status" />
                        </div>
                        <div class="flex gap-2">
                            @can('update', $fulfillment)
                                @if ($fulfillment->status === FulfillmentShipmentStatus::Pending)
                                    <flux:button size="sm" wire:click="markAsShipped({{ $fulfillment->id }})" data-test="mark-shipped-{{ $fulfillment->id }}">
                                        {{ __('Mark as shipped') }}
                                    </flux:button>
                                @elseif ($fulfillment->status === FulfillmentShipmentStatus::Shipped)
                                    <flux:button size="sm" wire:click="markAsDelivered({{ $fulfillment->id }})" data-test="mark-delivered-{{ $fulfillment->id }}">
                                        {{ __('Mark as delivered') }}
                                    </flux:button>
                                @endif
                            @endcan
                        </div>
                    </div>

                    @if (filled($fulfillment->tracking_number) || filled($fulfillment->tracking_company))
                        <flux:text class="mt-2 text-sm">
                            {{ trim(($fulfillment->tracking_company ?? '').' '.($fulfillment->tracking_number ?? '')) }}
                            @if (filled($fulfillment->tracking_url))
                                <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ __('Track shipment') }}
                                </a>
                            @endif
                        </flux:text>
                    @endif

                    <ul class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($fulfillment->lines as $fulfillmentLine)
                            <li wire:key="fulfillment-line-{{ $fulfillmentLine->id }}">
                                {{ $fulfillmentLine->quantity }} x {{ $fulfillmentLine->orderLine?->title_snapshot }}
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endforeach

            {{-- Order lines --}}
            <x-admin.card class="!p-0">
                <div class="p-6 pb-4">
                    <flux:heading>{{ __('Order lines') }}</flux:heading>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                <th class="w-14 px-6 py-2.5">{{ __('Image') }}</th>
                                <th class="px-4 py-2.5">{{ __('Product') }}</th>
                                <th class="px-4 py-2.5">{{ __('Fulfillment') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Qty') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Unit price') }}</th>
                                <th class="px-6 py-2.5 text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($order->lines as $line)
                                @php($lineMedia = $line->variant?->product?->media->first())
                                <tr wire:key="line-{{ $line->id }}">
                                    <td class="px-6 py-3">
                                        @if ($lineMedia !== null)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($lineMedia->storage_key) }}" alt="" class="size-10 rounded-md object-cover" />
                                        @else
                                            <div class="flex size-10 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon name="photo" variant="micro" class="text-zinc-400" />
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $line->title_snapshot }}</p>
                                        @if (filled($line->sku_snapshot))
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('SKU: :sku', ['sku' => $line->sku_snapshot]) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php($unfulfilled = $line->unfulfilledQuantity())
                                        <x-admin.status-badge :status="$unfulfilled === 0 ? 'fulfilled' : ($unfulfilled < $line->quantity ? 'partial' : 'unfulfilled')" />
                                    </td>
                                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ $line->quantity }}</td>
                                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ PriceFormatter::format($line->unit_price_amount, $order->currency) }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">{{ PriceFormatter::format($line->total_amount, $order->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="flex justify-end border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <dl class="w-full max-w-xs space-y-1.5 text-sm" data-test="order-totals">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">{{ PriceFormatter::format($order->subtotal_amount, $order->currency) }}</dd>
                        </div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Discount') }}</dt>
                                <dd class="text-zinc-800 dark:text-zinc-200">-{{ PriceFormatter::format($order->discount_amount, $order->currency) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Shipping') }}</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">{{ PriceFormatter::format($order->shipping_amount, $order->currency) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Tax') }}</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">{{ PriceFormatter::format($order->tax_amount, $order->currency) }}</dd>
                        </div>
                        <flux:separator class="my-1" />
                        <div class="flex justify-between font-semibold">
                            <dt class="text-zinc-800 dark:text-zinc-200">{{ __('Total') }}</dt>
                            <dd class="text-zinc-900 dark:text-white">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</dd>
                        </div>
                        @if ($order->refundedAmount() > 0)
                            <div class="flex justify-between text-red-600 dark:text-red-400">
                                <dt>{{ __('Refunded') }}</dt>
                                <dd>-{{ PriceFormatter::format($order->refundedAmount(), $order->currency) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </x-admin.card>

            {{-- Payment details --}}
            <x-admin.card :heading="__('Payment details')">
                <div class="space-y-3">
                    @forelse ($order->payments as $payment)
                        <div wire:key="payment-{{ $payment->id }}" class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ \Illuminate\Support\Str::headline($payment->method->value) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ PriceFormatter::format($payment->amount, $payment->currency) }}
                                    @if (filled($payment->provider_payment_id))
                                        - {{ __('Ref:') }} {{ $payment->provider_payment_id }}
                                    @endif
                                </p>
                            </div>
                            <x-admin.status-badge :status="$payment->status" />
                        </div>
                    @empty
                        <flux:text>{{ __('No payment recorded.') }}</flux:text>
                    @endforelse

                    @if ($showConfirmPayment)
                        @can('update', $order)
                            <flux:button variant="primary" size="sm" wire:click="confirmPayment" data-test="confirm-payment-button-panel">
                                {{ __('Confirm payment') }}
                            </flux:button>
                        @endcan
                    @endif
                </div>
            </x-admin.card>
        </div>

        {{-- RIGHT COLUMN (1/3) --}}
        <div class="space-y-6">
            <x-admin.card :heading="__('Customer')">
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $order->customer?->name ?? __('Guest') }}</p>
                <flux:text class="text-sm">{{ $order->email }}</flux:text>
                @if ($order->customer !== null)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate class="mt-2 inline-block text-sm text-blue-600 hover:underline dark:text-blue-400">
                        {{ __('View customer') }}
                    </a>
                @endif
            </x-admin.card>

            @foreach ([['heading' => __('Shipping address'), 'address' => $order->shipping_address_json], ['heading' => __('Billing address'), 'address' => $order->billing_address_json]] as $panel)
                <x-admin.card :heading="$panel['heading']" wire:key="address-{{ \Illuminate\Support\Str::slug($panel['heading']) }}">
                    @if (filled($panel['address']))
                        <div class="space-y-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                            @php($address = $panel['address'])
                            @if (filled(trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? ''))))
                                <p>{{ trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')) }}</p>
                            @endif
                            @if (filled($address['address1'] ?? null))<p>{{ $address['address1'] }}</p>@endif
                            @if (filled($address['address2'] ?? null))<p>{{ $address['address2'] }}</p>@endif
                            <p>
                                {{ trim(collect([$address['zip'] ?? $address['postal_code'] ?? null, $address['city'] ?? null])->filter()->implode(' ')) }}
                                {{ filled($address['province'] ?? null) ? ', '.$address['province'] : '' }}
                            </p>
                            @if (filled($address['country_code'] ?? null))
                                <p>{{ \App\Support\Storefront\Countries::name($address['country_code']) }}</p>
                            @endif
                        </div>
                    @else
                        <flux:text>{{ __('No address provided.') }}</flux:text>
                    @endif
                </x-admin.card>
            @endforeach
        </div>
    </div>

    {{-- Fulfillment modal --}}
    <flux:modal name="create-fulfillment" class="md:max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Create fulfillment') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($fulfillmentLines as $lineId => $line)
                    <div wire:key="fulfill-line-{{ $lineId }}" class="flex items-center gap-3">
                        <flux:checkbox wire:model="fulfillmentLines.{{ $lineId }}.selected" data-test="fulfill-line-checkbox-{{ $lineId }}" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $line['title'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __(':count unfulfilled', ['count' => $line['max']]) }}</p>
                        </div>
                        <flux:input
                            wire:model="fulfillmentLines.{{ $lineId }}.quantity"
                            type="number"
                            min="1"
                            max="{{ $line['max'] }}"
                            size="sm"
                            class="w-20"
                            data-test="fulfill-line-quantity-{{ $lineId }}"
                        />
                    </div>
                @empty
                    <flux:text>{{ __('All lines are fulfilled.') }}</flux:text>
                @endforelse
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
                <flux:input wire:model="trackingUrl" type="url" placeholder="https://" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createFulfillment" data-test="submit-fulfillment-button">
                    {{ __('Create fulfillment') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund modal --}}
    <flux:modal name="create-refund" class="md:max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Refund order') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($refundLines as $lineId => $line)
                    <div wire:key="refund-line-{{ $lineId }}" class="flex items-center gap-3">
                        <flux:checkbox wire:model="refundLines.{{ $lineId }}.selected" data-test="refund-line-checkbox-{{ $lineId }}" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $line['title'] }}</p>
                        </div>
                        <flux:input
                            wire:model="refundLines.{{ $lineId }}.quantity"
                            type="number"
                            min="0"
                            max="{{ $line['max'] }}"
                            size="sm"
                            class="w-20"
                        />
                    </div>
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label>{{ __('Or enter custom amount') }}</flux:label>
                <flux:input wire:model="refundAmount" type="number" step="0.01" min="0" placeholder="0.00" data-test="refund-amount-input" />
                <flux:description>
                    {{ __('Remaining refundable: :amount', ['amount' => PriceFormatter::format($order->remainingRefundableAmount(), $order->currency)]) }}
                </flux:description>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Reason') }}</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" :placeholder="__('Reason for refund...')" data-test="refund-reason-input" />
            </flux:field>

            <flux:checkbox wire:model="refundRestock" :label="__('Restock items')" data-test="refund-restock-checkbox" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="createRefund" data-test="submit-refund-button">
                    {{ __('Create refund') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel order modal --}}
    <flux:modal name="cancel-order" class="md:max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel this order?') }}</flux:heading>
            <flux:text>
                {{ __('Pending orders release their inventory reservation; paid orders are restocked. This cannot be undone.') }}
            </flux:text>

            <flux:field>
                <flux:label>{{ __('Reason') }}</flux:label>
                <flux:textarea wire:model="cancelReason" rows="2" :placeholder="__('Reason for cancellation...')" data-test="cancel-reason-input" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Keep order') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancelOrder" data-test="submit-cancel-order-button">
                    {{ __('Cancel order') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
