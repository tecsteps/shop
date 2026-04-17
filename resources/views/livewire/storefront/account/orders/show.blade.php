<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.orders') }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" wire:navigate>
            <flux:icon name="arrow-left" class="size-5" />
        </a>
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order #{{ $order->order_number }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Placed on {{ $order->placed_at?->format('F d, Y') }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <x-storefront.badge :variant="match($order->status->value) { 'paid' => 'new', 'fulfilled' => 'new', 'cancelled' => 'sold-out', 'refunded' => 'sold-out', default => 'draft' }">
            {{ ucfirst($order->status->value) }}
        </x-storefront.badge>
        <x-storefront.badge :variant="match($order->financial_status->value) { 'paid' => 'new', 'partially_refunded' => 'draft', 'refunded' => 'sold-out', 'voided' => 'sold-out', default => 'draft' }">
            {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
        </x-storefront.badge>
        <x-storefront.badge :variant="match($order->fulfillment_status->value) { 'fulfilled' => 'new', 'partial' => 'draft', default => 'draft' }">
            {{ ucfirst($order->fulfillment_status->value) }}
        </x-storefront.badge>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Items</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-2 font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="text-right py-2 font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                        <th class="text-right py-2 font-medium text-zinc-500 dark:text-zinc-400">Qty</th>
                        <th class="text-right py-2 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr wire:key="line-{{ $line->id }}" class="border-b border-zinc-100 dark:border-zinc-700/50 last:border-0">
                            <td class="py-3">
                                <span class="text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</span>
                                @if ($line->variant_title_snapshot && $line->variant_title_snapshot !== 'Default')
                                    <span class="text-zinc-500 dark:text-zinc-400"> - {{ $line->variant_title_snapshot }}</span>
                                @endif
                                @if ($line->sku_snapshot)
                                    <span class="block text-xs text-zinc-400 dark:text-zinc-500">SKU: {{ $line->sku_snapshot }}</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <x-storefront.price :amount="$line->unit_price_amount" :currency="$order->currency" />
                            </td>
                            <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ $line->quantity }}</td>
                            <td class="py-3 text-right">
                                <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-6 mb-6">
        @if ($order->shipping_address_json)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Shipping Address</h2>
                @php $addr = $order->shipping_address_json; @endphp
                <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                    <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                    <p>{{ $addr['address1'] ?? '' }}</p>
                    @if (!empty($addr['address2']))
                        <p>{{ $addr['address2'] }}</p>
                    @endif
                    <p>{{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}, {{ $addr['country_code'] ?? $addr['country'] ?? '' }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Order Totals</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                    <dd><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd>
                </div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <dt>Discount</dt>
                        <dd>-<x-storefront.price :amount="$order->discount_amount" :currency="$order->currency" /></dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
                    <dd><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd>
                </div>
                @if ($order->tax_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
                        <dd><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></dd>
                    </div>
                @endif
                <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <dt class="font-semibold text-zinc-900 dark:text-white">Total</dt>
                    <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="font-semibold" /></dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($order->fulfillments->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Fulfillment</h2>
            @foreach ($order->fulfillments as $fulfillment)
                <div wire:key="fulfillment-{{ $fulfillment->id }}" class="mb-4 last:mb-0">
                    <div class="flex items-center gap-2 mb-2">
                        <x-storefront.badge :variant="match($fulfillment->status->value) { 'delivered' => 'new', 'shipped' => 'new', default => 'draft' }">
                            {{ ucfirst($fulfillment->status->value) }}
                        </x-storefront.badge>
                    </div>
                    @if ($fulfillment->tracking_number)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Tracking: {{ $fulfillment->tracking_company ? $fulfillment->tracking_company . ' - ' : '' }}{{ $fulfillment->tracking_number }}
                        </p>
                    @endif
                    @if ($fulfillment->shipped_at)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Shipped: {{ $fulfillment->shipped_at->format('M d, Y') }}</p>
                    @endif
                    @if ($fulfillment->delivered_at)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Delivered: {{ $fulfillment->delivered_at->format('M d, Y') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
