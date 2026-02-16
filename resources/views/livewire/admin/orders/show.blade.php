<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">#{{ $order->order_number }}</h2>
            <flux:badge :variant="match($order->financial_status?->value) { 'paid' => 'primary', 'pending' => 'warning', 'refunded' => 'danger', default => 'outline' }">
                {{ ucfirst(str_replace('_', ' ', $order->financial_status?->value ?? 'unknown')) }}
            </flux:badge>
            <flux:badge :variant="match($order->fulfillment_status?->value) { 'fulfilled' => 'primary', 'partial' => 'warning', default => 'outline' }">
                {{ ucfirst($order->fulfillment_status?->value ?? 'unfulfilled') }}
            </flux:badge>
        </div>
        <div class="flex gap-2">
            @if($order->financial_status === \App\Enums\FinancialStatus::Pending && $order->payment_method === 'bank_transfer')
                <flux:button wire:click="confirmPayment" variant="primary" size="sm">Confirm payment</flux:button>
            @endif
            @if($order->financial_status !== \App\Enums\FinancialStatus::Pending)
                <flux:button wire:click="openFulfillmentModal" variant="primary" size="sm">Create fulfillment</flux:button>
            @elseif($order->financial_status === \App\Enums\FinancialStatus::Pending)
                <p class="text-sm text-amber-600 dark:text-amber-400">Payment must be confirmed before fulfillment.</p>
            @endif
            <flux:button wire:click="openRefundModal" variant="ghost" size="sm">Refund</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Line items --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">SKU</th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($order->lines as $line)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $line->title_snapshot }}
                                        @if($line->variant_title_snapshot)
                                            <span class="text-gray-500 dark:text-gray-400">- {{ $line->variant_title_snapshot }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $line->sku_snapshot ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ $line->quantity }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">${{ number_format($line->unit_price / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Totals --}}
                <div class="border-t border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div class="ml-auto w-64 space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Subtotal</span><span class="text-gray-900 dark:text-white">${{ number_format($order->subtotal / 100, 2) }}</span></div>
                        @if($order->discount_amount)
                            <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Discount</span><span class="text-green-600">-${{ number_format($order->discount_amount / 100, 2) }}</span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Shipping</span><span class="text-gray-900 dark:text-white">${{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tax</span><span class="text-gray-900 dark:text-white">${{ number_format($order->tax_amount / 100, 2) }}</span></div>
                        <div class="flex justify-between border-t pt-1 font-semibold dark:border-zinc-700"><span class="text-gray-900 dark:text-white">Total</span><span class="text-gray-900 dark:text-white">${{ number_format($order->total / 100, 2) }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Fulfillments --}}
            @if($order->fulfillments->count())
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Fulfillments</h3>
                    @foreach($order->fulfillments as $fulfillment)
                        <div class="mb-4 rounded border border-gray-100 p-4 dark:border-zinc-700">
                            <div class="mb-2 flex items-center justify-between">
                                <flux:badge size="sm" :variant="match($fulfillment->status->value) { 'delivered' => 'primary', 'shipped' => 'warning', default => 'outline' }">
                                    {{ ucfirst($fulfillment->status->value) }}
                                </flux:badge>
                                <div class="flex gap-2">
                                    @if($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                                        <flux:button wire:click="markAsShipped({{ $fulfillment->id }})" size="sm" variant="ghost">Mark shipped</flux:button>
                                    @elseif($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                                        <flux:button wire:click="markAsDelivered({{ $fulfillment->id }})" size="sm" variant="ghost">Mark delivered</flux:button>
                                    @endif
                                </div>
                            </div>
                            @if($fulfillment->tracking_number)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}</p>
                            @endif
                            <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                @foreach($fulfillment->lines as $fl)
                                    <li>{{ $fl->orderLine?->title_snapshot }} x {{ $fl->quantity }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Refunds --}}
            @if($order->refunds->count())
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Refunds</h3>
                    @foreach($order->refunds as $refund)
                        <div class="mb-2 flex items-center justify-between rounded border border-gray-100 p-3 dark:border-zinc-700">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($refund->amount / 100, 2) }}</span>
                                @if($refund->reason) <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $refund->reason }}</span> @endif
                            </div>
                            <flux:badge size="sm">{{ ucfirst($refund->status->value) }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Customer</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $order->email }}</p>
                @if($order->customer)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer->first_name }} {{ $order->customer->last_name }}</p>
                @endif
            </div>

            {{-- Shipping address --}}
            @if($order->shipping_address_json)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Shipping address</h3>
                    @php $addr = $order->shipping_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                        <p>{{ $addr['address1'] ?? '' }}</p>
                        @if(!empty($addr['address2'])) <p>{{ $addr['address2'] }}</p> @endif
                        <p>{{ $addr['city'] ?? '' }}, {{ $addr['province'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Billing address --}}
            @if($order->billing_address_json)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Billing address</h3>
                    @php $baddr = $order->billing_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $baddr['first_name'] ?? '' }} {{ $baddr['last_name'] ?? '' }}</p>
                        <p>{{ $baddr['address1'] ?? '' }}</p>
                        <p>{{ $baddr['city'] ?? '' }}, {{ $baddr['province'] ?? '' }} {{ $baddr['zip'] ?? '' }}</p>
                        <p>{{ $baddr['country'] ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Payment info --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Payment</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</p>
                @foreach($order->payments as $payment)
                    <div class="mt-2 text-sm">
                        <flux:badge size="sm">{{ ucfirst($payment->status->value) }}</flux:badge>
                        @if($payment->provider_payment_id)
                            <span class="ml-1 text-gray-500 dark:text-gray-400">{{ $payment->provider_payment_id }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal wire:model="showFulfillmentModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Fulfillment</h3>
            <div class="space-y-3">
                @foreach($order->lines as $i => $line)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $line->title_snapshot }}</span>
                        <flux:input wire:model="fulfillmentLines.{{ $i }}.quantity" type="number" min="0" max="{{ $line->quantity }}" class="w-20" />
                    </div>
                @endforeach
            </div>
            <flux:input wire:model="trackingCompany" label="Tracking company" />
            <flux:input wire:model="trackingNumber" label="Tracking number" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showFulfillmentModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createFulfillment" variant="primary">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Issue Refund</h3>
            <flux:input wire:model="refundAmount" label="Amount" type="number" step="0.01" />
            <flux:input wire:model="refundReason" label="Reason" />
            <flux:checkbox wire:model="refundRestock" label="Restock items" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRefundModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createRefund" variant="primary">Issue refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
