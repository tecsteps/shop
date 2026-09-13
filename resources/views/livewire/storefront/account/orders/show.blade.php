<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Account', 'url' => route('customer.account')], ['label' => 'Orders', 'url' => route('customer.orders.index')], ['label' => $order->order_number]]" />

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <h1 class="text-3xl font-bold">Order {{ $order->order_number }}</h1>
        <x-storefront.badge :text="ucfirst($order->status->value)" />
        <x-storefront.badge :text="ucfirst(str_replace('_', ' ', $order->fulfillment_status->value))" />
    </div>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Placed on {{ $order->placed_at?->format('F j, Y') }}</p>

    <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
        <h2 class="font-semibold">Items</h2>
        <ul class="mt-4 flex flex-col gap-3">
            @foreach ($order->orderLines as $line)
                <li wire:key="order-line-{{ $line->getKey() }}" class="flex items-center justify-between gap-4 text-sm">
                    <span>{{ $line->title_snapshot }} x{{ $line->quantity }}</span>
                    <span class="font-semibold">{{ \App\Support\Money::format($line->total_amount, $order->currency) }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        <div>
            <h2 class="text-sm font-semibold">Shipping Address</h2>
            @php $a = (array) $order->shipping_address_json; @endphp
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br />
                {{ $a['address1'] ?? '' }}<br />
                {{ $a['city'] ?? '' }} {{ $a['postal_code'] ?? '' }}
            </p>
        </div>
        <div>
            <h2 class="text-sm font-semibold">Billing Address</h2>
            @php $b = (array) $order->billing_address_json; @endphp
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $b['first_name'] ?? '' }} {{ $b['last_name'] ?? '' }}<br />
                {{ $b['address1'] ?? '' }}<br />
                {{ $b['city'] ?? '' }} {{ $b['postal_code'] ?? '' }}
            </p>
        </div>
        <div>
            <h2 class="text-sm font-semibold">Payment</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $order->payment_method?->value ?? 'unknown')) }}</p>
        </div>
    </div>

    <dl class="mt-6 flex max-w-md flex-col gap-1 text-sm">
        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }}</dd></div>
        <div class="flex justify-between"><dt>Shipping</dt><dd>{{ \App\Support\Money::format($order->shipping_amount, $order->currency) }}</dd></div>
        <div class="flex justify-between"><dt>Tax</dt><dd>{{ \App\Support\Money::format($order->tax_amount, $order->currency) }}</dd></div>
        <div class="flex justify-between"><dt>Discount</dt><dd>-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</dd></div>
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-700"><dt>Total</dt><dd>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</dd></div>
    </dl>

    @if ($order->fulfillments->isNotEmpty())
        <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="font-semibold">Fulfillment</h2>
            @foreach ($order->fulfillments as $fulfillment)
                <div wire:key="fulfillment-{{ $fulfillment->getKey() }}" class="mt-2 text-sm">
                    <p>Status: {{ ucfirst($fulfillment->status->value) }}</p>
                    @if ($fulfillment->tracking_number)
                        <p>Shipped via {{ $fulfillment->tracking_company ?? 'carrier' }} - {{ $fulfillment->tracking_number }}</p>
                    @endif
                    @if ($fulfillment->tracking_url)
                        <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">Track shipment</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
