<div>
    <x-slot:title>Order {{ $order->order_number }}</x-slot:title>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
            <div class="mt-1 flex items-center gap-2">
                <flux:badge :color="match($order->status->value) { 'pending' => 'yellow', 'paid' => 'green', 'fulfilled' => 'green', 'cancelled' => 'red', default => 'zinc' }">
                    {{ ucfirst($order->status->value) }}
                </flux:badge>
                <flux:text class="text-sm">{{ $order->placed_at?->format('M d, Y \a\t g:i A') ?? '-' }}</flux:text>
            </div>
        </div>
        <div class="flex gap-2">
            @if ($order->fulfillment_status->value !== 'fulfilled')
                <flux:button wire:click="openFulfillmentModal" variant="primary">Fulfill</flux:button>
            @endif
            @if ($order->financial_status->value === 'paid')
                <flux:button wire:click="openRefundModal" variant="ghost">Refund</flux:button>
            @endif
            <flux:button href="{{ route('admin.orders.index') }}" variant="ghost" wire:navigate>Back</flux:button>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Line Items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <flux:heading size="lg">Items</flux:heading>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($order->lines as $line)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="font-medium">{{ $line->title_snapshot }}</p>
                                <p class="text-sm text-gray-500">{{ $line->quantity }} x {{ number_format($line->unit_price_amount / 100, 2) }} {{ $order->currency }}</p>
                            </div>
                            <flux:text class="font-medium">{{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Fulfillments --}}
            @if ($order->fulfillments->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <flux:heading size="lg">Fulfillments</flux:heading>
                    </div>
                    @foreach ($order->fulfillments as $fulfillment)
                        <div class="px-6 py-4 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                            <div class="flex items-center justify-between">
                                <flux:text class="font-medium">Fulfillment #{{ $loop->iteration }}</flux:text>
                                <flux:text class="text-sm">{{ $fulfillment->created_at->format('M d, Y') }}</flux:text>
                            </div>
                            @if ($fulfillment->tracking_number)
                                <flux:text class="mt-1 text-sm">Tracking: {{ $fulfillment->tracking_number }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Refunds --}}
            @if ($order->refunds->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <flux:heading size="lg">Refunds</flux:heading>
                    </div>
                    @foreach ($order->refunds as $refund)
                        <div class="px-6 py-4 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                            <div class="flex items-center justify-between">
                                <flux:text class="font-medium">{{ number_format($refund->amount / 100, 2) }} {{ $order->currency }}</flux:text>
                                <flux:badge :color="$refund->status->value === 'completed' ? 'green' : 'yellow'">
                                    {{ ucfirst($refund->status->value) }}
                                </flux:badge>
                            </div>
                            @if ($refund->reason)
                                <flux:text class="mt-1 text-sm">{{ $refund->reason }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Summary</flux:heading>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd>{{ number_format($order->subtotal_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Discount</dt>
                            <dd class="text-green-600">-{{ number_format($order->discount_amount / 100, 2) }} {{ $order->currency }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Shipping</dt>
                        <dd>{{ number_format($order->shipping_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Tax</dt>
                        <dd>{{ number_format($order->tax_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                    <div class="flex justify-between border-t pt-2 font-semibold dark:border-gray-700">
                        <dt>Total</dt>
                        <dd>{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Customer</flux:heading>
                @if ($order->customer)
                    <div class="mt-4">
                        <a href="{{ route('admin.customers.show', $order->customer) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                            {{ $order->customer->name }}
                        </a>
                        <p class="text-sm text-gray-500">{{ $order->customer->email }}</p>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500">{{ $order->email ?? 'Guest' }}</p>
                @endif
            </div>

            @if ($order->shipping_address_json)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <flux:heading size="lg">Shipping Address</flux:heading>
                    <address class="mt-4 text-sm not-italic text-gray-600 dark:text-gray-400">
                        {{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}<br>
                        {{ $order->shipping_address_json['address1'] ?? '' }}<br>
                        {{ $order->shipping_address_json['zip'] ?? '' }} {{ $order->shipping_address_json['city'] ?? '' }}<br>
                        {{ $order->shipping_address_json['country_code'] ?? '' }}
                    </address>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal wire:model="showFulfillmentModal">
        <div class="space-y-4">
            <flux:heading size="lg">Create Fulfillment</flux:heading>
            <flux:field>
                <flux:label>Tracking Number (optional)</flux:label>
                <flux:input wire:model="trackingNumber" />
            </flux:field>
            <flux:field>
                <flux:label>Tracking URL (optional)</flux:label>
                <flux:input wire:model="trackingUrl" type="url" />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showFulfillmentModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createFulfillment" variant="primary">Create Fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal">
        <div class="space-y-4">
            <flux:heading size="lg">Process Refund</flux:heading>
            <flux:field>
                <flux:label>Refund Amount (cents)</flux:label>
                <flux:input wire:model="refundAmount" type="number" min="1" :max="$order->total_amount" />
            </flux:field>
            <flux:field>
                <flux:label>Reason (optional)</flux:label>
                <flux:textarea wire:model="refundReason" rows="2" />
            </flux:field>
            <flux:checkbox wire:model="restockItems" label="Restock items" />
            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showRefundModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createRefund" variant="primary">Process Refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
