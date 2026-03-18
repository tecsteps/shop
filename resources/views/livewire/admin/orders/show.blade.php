<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Order') }} {{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $order->placed_at?->format('M d, Y g:i A') ?? '-' }}</flux:text>
        </div>
        <div class="flex gap-2">
            @if($order->financial_status === \App\Enums\FinancialStatus::Pending && $order->payment_method === \App\Enums\PaymentMethod::BankTransfer)
                <flux:button variant="primary" wire:click="confirmPayment">{{ __('Confirm payment') }}</flux:button>
            @endif
            @if($order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled && $order->financial_status === \App\Enums\FinancialStatus::Paid)
                <flux:button wire:click="openFulfillmentModal">{{ __('Create fulfillment') }}</flux:button>
            @endif
            @if(in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                <flux:button variant="ghost" wire:click="openRefundModal">{{ __('Refund') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status Badges --}}
            <div class="flex gap-2">
                <flux:badge :color="match($order->financial_status->value) {
                    'paid' => 'green', 'pending' => 'yellow', 'refunded' => 'red',
                    'partially_refunded' => 'orange', default => 'zinc',
                }">{{ str_replace('_', ' ', ucfirst($order->financial_status->value)) }}</flux:badge>
                <flux:badge :color="match($order->fulfillment_status->value) {
                    'fulfilled' => 'green', 'partial' => 'yellow', default => 'zinc',
                }">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
            </div>

            {{-- Line Items --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="md">{{ __('Items') }}</flux:heading>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Product') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('SKU') }}</th>
                            <th class="p-3 text-right font-medium text-zinc-500">{{ __('Price') }}</th>
                            <th class="p-3 text-right font-medium text-zinc-500">{{ __('Qty') }}</th>
                            <th class="p-3 text-right font-medium text-zinc-500">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->lines as $line)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-3">
                                    <div>{{ $line->title_snapshot }}</div>
                                    @if($line->variant_title_snapshot)
                                        <flux:text class="text-xs text-zinc-500">{{ $line->variant_title_snapshot }}</flux:text>
                                    @endif
                                </td>
                                <td class="p-3 text-zinc-500">{{ $line->sku_snapshot ?? '-' }}</td>
                                <td class="p-3 text-right">${{ number_format($line->price_amount / 100, 2) }}</td>
                                <td class="p-3 text-right">{{ $line->quantity }}</td>
                                <td class="p-3 text-right">${{ number_format($line->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4 space-y-1 text-sm text-right">
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500">{{ __('Subtotal') }}</span>
                        <span>${{ number_format($order->subtotal_amount / 100, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-end gap-8">
                            <span class="text-zinc-500">{{ __('Discount') }}</span>
                            <span class="text-red-500">-${{ number_format($order->discount_amount / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500">{{ __('Shipping') }}</span>
                        <span>${{ number_format($order->shipping_amount / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-end gap-8">
                        <span class="text-zinc-500">{{ __('Tax') }}</span>
                        <span>${{ number_format($order->tax_amount / 100, 2) }}</span>
                    </div>
                    <flux:separator class="my-2" />
                    <div class="flex justify-end gap-8 font-bold">
                        <span>{{ __('Total') }}</span>
                        <span>${{ number_format($order->total_amount / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Fulfillments --}}
            @if($order->fulfillments->count() > 0)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="md">{{ __('Fulfillments') }}</flux:heading>
                    </div>
                    @foreach($order->fulfillments as $fulfillment)
                        <div class="p-4 @if(!$loop->last) border-b border-zinc-100 dark:border-zinc-800 @endif">
                            <div class="flex justify-between items-center mb-2">
                                <flux:badge size="sm">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                                <flux:text class="text-zinc-500 text-xs">{{ $fulfillment->created_at?->diffForHumans() }}</flux:text>
                            </div>
                            @if($fulfillment->tracking_number)
                                <flux:text class="text-sm">
                                    {{ __('Tracking') }}: {{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}
                                </flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Refunds --}}
            @if($order->refunds->count() > 0)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="md">{{ __('Refunds') }}</flux:heading>
                    </div>
                    @foreach($order->refunds as $refund)
                        <div class="p-4 @if(!$loop->last) border-b border-zinc-100 dark:border-zinc-800 @endif">
                            <div class="flex justify-between">
                                <span>${{ number_format($refund->amount / 100, 2) }}</span>
                                <flux:badge size="sm" :color="$refund->status->value === 'processed' ? 'green' : 'red'">
                                    {{ ucfirst($refund->status->value) }}
                                </flux:badge>
                            </div>
                            @if($refund->reason)
                                <flux:text class="text-sm text-zinc-500 mt-1">{{ $refund->reason }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <flux:heading size="md" class="mb-3">{{ __('Customer') }}</flux:heading>
                @if($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="text-accent hover:underline" wire:navigate>
                        {{ $order->customer->name ?? $order->customer->email }}
                    </a>
                @else
                    <flux:text>{{ $order->email }}</flux:text>
                @endif
            </div>

            {{-- Payment Info --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <flux:heading size="md" class="mb-3">{{ __('Payment') }}</flux:heading>
                <flux:text>{{ str_replace('_', ' ', ucfirst($order->payment_method->value)) }}</flux:text>
                <flux:text class="text-zinc-500">{{ $order->currency }}</flux:text>
            </div>

            {{-- Shipping Address --}}
            @if($order->shipping_address_json)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                    <flux:heading size="md" class="mb-3">{{ __('Shipping address') }}</flux:heading>
                    <div class="text-sm space-y-1">
                        @php $addr = $order->shipping_address_json; @endphp
                        <div>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</div>
                        <div>{{ $addr['address1'] ?? '' }}</div>
                        @if(!empty($addr['address2']))<div>{{ $addr['address2'] }}</div>@endif
                        <div>{{ $addr['city'] ?? '' }}, {{ $addr['province_code'] ?? '' }} {{ $addr['zip'] ?? '' }}</div>
                        <div>{{ $addr['country_code'] ?? '' }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal wire:model="showFulfillmentModal" name="fulfillment-modal">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Create fulfillment') }}</flux:heading>

            <div class="space-y-3">
                @foreach($order->lines as $line)
                    <div class="flex items-center justify-between gap-4">
                        <flux:text>{{ $line->title_snapshot }}</flux:text>
                        <flux:input
                            type="number"
                            wire:model="fulfillmentQuantities.{{ $line->id }}"
                            min="0"
                            :max="$line->quantity - $line->fulfilled_quantity"
                            class="w-20"
                            size="sm"
                        />
                    </div>
                @endforeach
            </div>

            <flux:separator />

            <flux:field>
                <flux:label>{{ __('Tracking company') }}</flux:label>
                <flux:input wire:model="trackingCompany" placeholder="{{ __('DHL, UPS, FedEx...') }}" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Tracking number') }}</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Tracking URL') }}</flux:label>
                <flux:input wire:model="trackingUrl" type="url" />
            </flux:field>

            <div class="flex justify-end gap-4">
                <flux:button variant="ghost" wire:click="$set('showFulfillmentModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="createFulfillment">{{ __('Fulfill items') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" name="refund-modal">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Refund order') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Refund amount (cents)') }}</flux:label>
                <flux:input wire:model="refundAmount" type="number" min="1" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Reason') }}</flux:label>
                <flux:textarea wire:model="refundReason" rows="3" />
            </flux:field>
            <flux:checkbox wire:model="refundRestock" label="{{ __('Restock items') }}" />

            <div class="flex justify-end gap-4">
                <flux:button variant="ghost" wire:click="$set('showRefundModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="createRefund">{{ __('Process refund') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
