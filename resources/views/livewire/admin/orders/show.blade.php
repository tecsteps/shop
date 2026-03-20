<div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Orders</a>

    <div class="mt-4 flex items-center justify-between">
        <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
        <div class="flex gap-2">
            @if($order->status->value === 'pending' && $order->payment_method->value === 'bank_transfer' && $order->financial_status->value === 'pending')
                <flux:button wire:click="confirmBankTransfer" variant="primary" size="sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmBankTransfer">Confirm Payment</span>
                    <span wire:loading wire:target="confirmBankTransfer">Confirming...</span>
                </flux:button>
            @endif
            @if($order->fulfillment_status->value !== 'fulfilled' && $order->status->value !== 'cancelled')
                <flux:button wire:click="cancelOrder" wire:confirm="Cancel this order?" variant="danger" size="sm" wire:loading.attr="disabled" wire:target="cancelOrder">Cancel</flux:button>
            @endif
        </div>
    </div>

    <div class="mt-2 flex gap-2">
        <flux:badge :color="match($order->status->value) { 'paid' => 'green', 'fulfilled' => 'blue', 'cancelled' => 'red', default => 'zinc' }">
            {{ ucfirst($order->status->value) }}
        </flux:badge>
        <flux:badge :color="match($order->financial_status->value) { 'paid' => 'green', 'refunded' => 'yellow', default => 'zinc' }">
            {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
        </flux:badge>
        <flux:badge :color="match($order->fulfillment_status->value) { 'fulfilled' => 'green', 'partial' => 'blue', default => 'zinc' }">
            {{ ucfirst($order->fulfillment_status->value) }}
        </flux:badge>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Line items --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Items</flux:heading>
                <table class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="pb-2 font-medium">Product</th>
                            <th class="pb-2 font-medium">SKU</th>
                            <th class="pb-2 text-center font-medium">Qty</th>
                            <th class="pb-2 text-right font-medium">Price</th>
                            <th class="pb-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($order->lines as $line)
                            <tr wire:key="line-{{ $line->id }}">
                                <td class="py-2 text-gray-900 dark:text-white">{{ $line->title_snapshot }}</td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ $line->sku_snapshot ?? '-' }}</td>
                                <td class="py-2 text-center text-gray-500 dark:text-gray-400">{{ $line->quantity }}</td>
                                <td class="py-2 text-right text-gray-500 dark:text-gray-400">${{ number_format($line->unit_price / 100, 2) }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">${{ number_format($line->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Subtotal</dt><dd class="text-gray-900 dark:text-white">${{ number_format($order->subtotal_amount / 100, 2) }}</dd></div>
                        @if($order->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Discount</dt><dd class="text-green-600 dark:text-green-400">-${{ number_format($order->discount_amount / 100, 2) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Shipping</dt><dd class="text-gray-900 dark:text-white">${{ number_format($order->shipping_amount / 100, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Tax</dt><dd class="text-gray-900 dark:text-white">${{ number_format($order->tax_amount / 100, 2) }}</dd></div>
                        <div class="flex justify-between border-t pt-1 font-semibold text-gray-900 dark:border-gray-700 dark:text-white"><dt>Total</dt><dd>${{ number_format($order->total_amount / 100, 2) }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Fulfillments --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Fulfillment</flux:heading>
                @if($order->fulfillments->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach($order->fulfillments as $f)
                            <div wire:key="fulfillment-{{ $f->id }}" class="rounded border border-gray-100 p-3 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Fulfillment #{{ $loop->iteration }}</span>
                                    <flux:badge size="sm" :color="match($f->status->value) { 'delivered' => 'green', 'shipped' => 'blue', default => 'zinc' }">
                                        {{ ucfirst($f->status->value) }}
                                    </flux:badge>
                                </div>
                                @if($f->tracking_number)
                                    <p class="mt-1 text-xs text-gray-500">Tracking: {{ $f->tracking_number }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($order->fulfillment_status->value !== 'fulfilled' && in_array($order->financial_status->value, ['paid', 'partially_refunded']))
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="text-sm font-medium">Create fulfillment</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            <flux:input wire:model="trackingNumber" placeholder="Tracking #" size="sm" />
                            <flux:input wire:model="trackingUrl" placeholder="Tracking URL" size="sm" />
                            <flux:input wire:model="trackingCompany" placeholder="Carrier" size="sm" />
                        </div>
                        <flux:button wire:click="createFulfillment" size="sm" variant="primary" class="mt-2" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="createFulfillment">Fulfill remaining items</span>
                            <span wire:loading wire:target="createFulfillment">Fulfilling...</span>
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Refunds --}}
            @if($order->refunds->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <flux:heading size="lg">Refunds</flux:heading>
                    <div class="mt-4 space-y-2">
                        @foreach($order->refunds as $refund)
                            <div wire:key="refund-{{ $refund->id }}" class="flex items-center justify-between rounded border border-gray-100 p-3 text-sm dark:border-gray-700">
                                <div>
                                    <span class="font-medium">${{ number_format($refund->amount / 100, 2) }}</span>
                                    @if($refund->reason)
                                        <span class="ml-2 text-gray-500">{{ $refund->reason }}</span>
                                    @endif
                                </div>
                                <flux:badge size="sm">{{ ucfirst($refund->status->value) }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Customer --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Customer</flux:heading>
                <div class="mt-3 text-sm">
                    @if($order->customer)
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer->name }}</p>
                        <p class="text-gray-500">{{ $order->customer->email }}</p>
                    @else
                        <p class="text-gray-500">{{ $order->email }}</p>
                    @endif
                </div>
            </div>

            {{-- Payment --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Payment</flux:heading>
                <div class="mt-3 text-sm">
                    <p class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</p>
                    @foreach($order->payments as $payment)
                        <p wire:key="payment-{{ $payment->id }}" class="text-gray-500 dark:text-gray-400">{{ ucfirst($payment->status->value) }} - ${{ number_format($payment->amount / 100, 2) }}</p>
                    @endforeach
                </div>

                @if(in_array($order->financial_status->value, ['paid', 'partially_refunded']))
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="text-sm font-medium">Process refund</p>
                        <div class="mt-2 space-y-2">
                            <flux:input wire:model="refundAmount" type="number" placeholder="Amount (cents)" size="sm" />
                            <flux:input wire:model="refundReason" placeholder="Reason" size="sm" />
                            <flux:checkbox wire:model="refundRestock" label="Restock items" />
                            <flux:button wire:click="processRefund" size="sm" variant="danger" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="processRefund">Refund</span>
                                <span wire:loading wire:target="processRefund">Processing...</span>
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Addresses --}}
            @if($order->shipping_address_json)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <flux:heading size="lg">Shipping address</flux:heading>
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}</p>
                        <p>{{ $order->shipping_address_json['address1'] ?? '' }}</p>
                        <p>{{ $order->shipping_address_json['city'] ?? '' }}, {{ $order->shipping_address_json['province'] ?? '' }} {{ $order->shipping_address_json['zip'] ?? '' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
