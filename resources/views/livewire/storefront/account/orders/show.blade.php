<div>
    <x-slot:title>Order {{ $order->order_number }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">Order {{ $order->order_number }}</h1>
            <a href="{{ route('customer.orders') }}" class="text-sm text-blue-600 hover:text-blue-500">&larr; Back to Orders</a>
        </div>

        <div class="mt-2 flex items-center gap-3">
            <span @class([
                'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $order->status->value === 'pending',
                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => in_array($order->status->value, ['paid', 'fulfilled']),
                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $order->status->value === 'cancelled',
            ])>
                {{ ucfirst($order->status->value) }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Placed {{ $order->placed_at?->format('M d, Y \a\t g:i A') ?? '-' }}
            </span>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            {{-- Order Items --}}
            <div class="lg:col-span-2">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="font-semibold">Items</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($order->lines as $line)
                            <div class="flex items-center justify-between px-6 py-4">
                                <div>
                                    <p class="font-medium">{{ $line->title_snapshot }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qty: {{ $line->quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Fulfillments --}}
                @if ($order->fulfillments->isNotEmpty())
                    <div class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="font-semibold">Shipments</h2>
                        </div>
                        @foreach ($order->fulfillments as $fulfillment)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Fulfillment #{{ $loop->iteration }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $fulfillment->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                                @if ($fulfillment->tracking_number)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Tracking: {{ $fulfillment->tracking_number }}
                                        @if ($fulfillment->tracking_url)
                                            (<a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Track</a>)
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Order Summary Sidebar --}}
            <div class="space-y-6">
                {{-- Totals --}}
                <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="font-semibold">Summary</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Subtotal</dt>
                            <dd><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd>
                        </div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">Discount</dt>
                                <dd class="text-green-600">-<x-storefront.price :amount="$order->discount_amount" :currency="$order->currency" /></dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Shipping</dt>
                            <dd><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Tax</dt>
                            <dd><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></dd>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold dark:border-gray-700">
                            <dt>Total</dt>
                            <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd>
                        </div>
                    </dl>
                </div>

                {{-- Shipping Address --}}
                @if ($order->shipping_address_json)
                    <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                        <h2 class="font-semibold">Shipping Address</h2>
                        <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-400">
                            {{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}<br>
                            {{ $order->shipping_address_json['address1'] ?? '' }}<br>
                            @if (!empty($order->shipping_address_json['address2']))
                                {{ $order->shipping_address_json['address2'] }}<br>
                            @endif
                            {{ $order->shipping_address_json['zip'] ?? '' }} {{ $order->shipping_address_json['city'] ?? '' }}<br>
                            {{ $order->shipping_address_json['country_code'] ?? '' }}
                        </address>
                    </div>
                @endif

                {{-- Billing Address --}}
                @if ($order->billing_address_json)
                    <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                        <h2 class="font-semibold">Billing Address</h2>
                        <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-400">
                            {{ $order->billing_address_json['first_name'] ?? '' }} {{ $order->billing_address_json['last_name'] ?? '' }}<br>
                            {{ $order->billing_address_json['address1'] ?? '' }}<br>
                            @if (!empty($order->billing_address_json['address2']))
                                {{ $order->billing_address_json['address2'] }}<br>
                            @endif
                            {{ $order->billing_address_json['zip'] ?? '' }} {{ $order->billing_address_json['city'] ?? '' }}<br>
                            {{ $order->billing_address_json['country_code'] ?? '' }}
                        </address>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
