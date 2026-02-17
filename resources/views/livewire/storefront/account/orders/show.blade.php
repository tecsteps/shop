<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('storefront.account.orders.index') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to Orders</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Placed on {{ $order->placed_at?->format('F j, Y \a\t g:i A') ?? 'N/A' }}
        </p>
    </div>

    {{-- Status badges --}}
    <div class="mb-8 flex flex-wrap gap-3">
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium
            @if($order->status->value === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
            @elseif($order->status->value === 'fulfilled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
            @elseif($order->status->value === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
            @endif">
            {{ ucfirst($order->status->value) }}
        </span>
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
            Payment: {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
        </span>
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
            Fulfillment: {{ ucfirst($order->fulfillment_status->value) }}
        </span>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Line items --}}
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Items</h2>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->lines as $line)
                        <li class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $line->title_snapshot }}</p>
                                @if($line->sku_snapshot)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $line->sku_snapshot }}</p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ $line->quantity }}</p>
                            </div>
                            <span class="text-sm text-gray-900 dark:text-white">
                                {{ $order->currency }} {{ number_format($line->total_amount / 100, 2) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Fulfillment timeline --}}
            @if($order->fulfillments->isNotEmpty())
                <div class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Shipments</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($order->fulfillments as $fulfillment)
                            <div class="px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($fulfillment->status->value) }}
                                    </span>
                                    @if($fulfillment->shipped_at)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $fulfillment->shipped_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                                @if($fulfillment->tracking_number)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $fulfillment->tracking_company ?? 'Tracking' }}: {{ $fulfillment->tracking_number }}
                                        @if($fulfillment->tracking_url)
                                            <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400">Track</a>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Order summary --}}
        <div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Summary</h2>
                </div>
                <dl class="divide-y divide-gray-200 px-4 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Subtotal</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $order->currency }} {{ number_format($order->subtotal_amount / 100, 2) }}</dd>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between py-2">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Discount</dt>
                            <dd class="text-sm text-green-600 dark:text-green-400">-{{ $order->currency }} {{ number_format($order->discount_amount / 100, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between py-2">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Shipping</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $order->currency }} {{ number_format($order->shipping_amount / 100, 2) }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Tax</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $order->currency }} {{ number_format($order->tax_amount / 100, 2) }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm font-semibold text-gray-900 dark:text-white">Total</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Addresses --}}
            @if($order->shipping_address_json)
                <div class="mt-4 rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                    @php $addr = $order->shipping_address_json; @endphp
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                        {{ $addr['address1'] ?? '' }}<br>
                        @if(!empty($addr['address2'])){{ $addr['address2'] }}<br>@endif
                        {{ $addr['city'] ?? '' }}, {{ $addr['zip'] ?? '' }}<br>
                        {{ $addr['country'] ?? $addr['country_code'] ?? '' }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
