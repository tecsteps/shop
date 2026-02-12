<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order #{{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Placed on {{ $order->placed_at?->format('F j, Y \a\t g:i A') }}
            </p>
        </div>
        <a href="{{ route('storefront.account.orders') }}" wire:navigate
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            &larr; Back to orders
        </a>
    </div>

    {{-- Status badges --}}
    <div class="flex flex-wrap gap-2 mb-8">
        @include('livewire.storefront.account.orders._status-badge', ['status' => $order->status])
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
            Payment: {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
        </span>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
            Fulfillment: {{ ucfirst($order->fulfillment_status->value) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Line items --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-medium text-gray-900 dark:text-white">Items</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($order->lines as $line)
                        <div class="flex items-center gap-4 p-4">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">
                                    {{ $line->title_snapshot }}
                                </p>
                                @if($line->variant_title_snapshot)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $line->variant_title_snapshot }}
                                    </p>
                                @endif
                                @if($line->sku_snapshot)
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        SKU: {{ $line->sku_snapshot }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right text-sm">
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ number_format($line->unit_price_amount / 100, 2) }} x {{ $line->quantity }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar: Addresses, Payment, Totals --}}
        <div class="space-y-6">
            {{-- Totals --}}
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <h2 class="font-medium text-gray-900 dark:text-white mb-4">Summary</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                        <dd class="text-gray-900 dark:text-white">{{ number_format($order->subtotal_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                    @if($order->shipping_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Shipping</dt>
                            <dd class="text-gray-900 dark:text-white">{{ number_format($order->shipping_amount / 100, 2) }} {{ $order->currency }}</dd>
                        </div>
                    @endif
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Tax</dt>
                            <dd class="text-gray-900 dark:text-white">{{ number_format($order->tax_amount / 100, 2) }} {{ $order->currency }}</dd>
                        </div>
                    @endif
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Discount</dt>
                            <dd class="text-green-600 dark:text-green-400">-{{ number_format($order->discount_amount / 100, 2) }} {{ $order->currency }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-800 font-medium">
                        <dt class="text-gray-900 dark:text-white">Total</dt>
                        <dd class="text-gray-900 dark:text-white">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Shipping address --}}
            @if($order->shipping_address_json)
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                    <h2 class="font-medium text-gray-900 dark:text-white mb-3">Shipping Address</h2>
                    @php $addr = $order->shipping_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                        <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                        <p>{{ $addr['address1'] ?? '' }}</p>
                        @if(!empty($addr['address2']))
                            <p>{{ $addr['address2'] }}</p>
                        @endif
                        <p>{{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? $addr['country_code'] ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Billing address --}}
            @if($order->billing_address_json)
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                    <h2 class="font-medium text-gray-900 dark:text-white mb-3">Billing Address</h2>
                    @php $addr = $order->billing_address_json; @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                        <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                        <p>{{ $addr['address1'] ?? '' }}</p>
                        @if(!empty($addr['address2']))
                            <p>{{ $addr['address2'] }}</p>
                        @endif
                        <p>{{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}</p>
                        <p>{{ $addr['country'] ?? $addr['country_code'] ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Payment --}}
            @if($order->payments->isNotEmpty())
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                    <h2 class="font-medium text-gray-900 dark:text-white mb-3">Payment</h2>
                    @foreach($order->payments as $payment)
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <p>{{ ucfirst(str_replace('_', ' ', $payment->method?->value ?? 'Unknown')) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Status: {{ ucfirst($payment->status?->value ?? 'unknown') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
