<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
        <a href="{{ route('storefront.account.orders.index') }}">
            <flux:button variant="ghost" size="sm">Back to Orders</flux:button>
        </a>
    </div>

    {{-- Order Summary --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md" class="mb-3">Order Details</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Status</dt>
                    <dd><flux:badge size="sm">{{ ucfirst($order->status->value) }}</flux:badge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Payment</dt>
                    <dd><flux:badge size="sm">{{ ucfirst($order->financial_status->value) }}</flux:badge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Fulfillment</dt>
                    <dd><flux:badge size="sm">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Placed</dt>
                    <dd>{{ $order->placed_at?->format('M d, Y g:i A') ?? $order->created_at->format('M d, Y g:i A') }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md" class="mb-3">Shipping Address</flux:heading>
            @if ($order->shipping_address_json)
                <div class="text-sm">
                    <p>{{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['address1'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['city'] ?? '' }}, {{ $order->shipping_address_json['province_code'] ?? '' }} {{ $order->shipping_address_json['postal_code'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['country_code'] ?? '' }}</p>
                </div>
            @else
                <flux:text>No shipping address on file.</flux:text>
            @endif
        </div>
    </div>

    {{-- Line Items --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">Items</flux:heading>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($order->lines as $line)
                <div wire:key="line-{{ $line->id }}" class="flex items-center justify-between p-4">
                    <div>
                        <p class="font-medium">{{ $line->title_snapshot }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            SKU: {{ $line->sku_snapshot }} / Qty: {{ $line->quantity }}
                        </p>
                    </div>
                    <p class="font-medium">${{ number_format($line->total_amount / 100, 2) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Totals --}}
    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                <dd>${{ number_format($order->subtotal_amount / 100, 2) }}</dd>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Discount</dt>
                    <dd class="text-green-600 dark:text-green-400">-${{ number_format($order->discount_amount / 100, 2) }}</dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Shipping</dt>
                <dd>${{ number_format($order->shipping_amount / 100, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
                <dd>${{ number_format($order->tax_amount / 100, 2) }}</dd>
            </div>
            <flux:separator />
            <div class="flex justify-between text-base font-semibold">
                <dt>Total</dt>
                <dd>${{ number_format($order->total_amount / 100, 2) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Fulfillment Timeline --}}
    @if ($order->fulfillments->isNotEmpty())
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md" class="mb-3">Fulfillment Timeline</flux:heading>
            <div class="space-y-3">
                @foreach ($order->fulfillments as $fulfillment)
                    <div wire:key="fulfillment-{{ $fulfillment->id }}" class="rounded-md bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:badge size="sm">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $fulfillment->created_at->format('M d, Y') }}</span>
                        </div>
                        @if ($fulfillment->tracking_number)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                Tracking: {{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
