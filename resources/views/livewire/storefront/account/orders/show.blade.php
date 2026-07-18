@php
    $statusVariant = fn (string $status) => match ($status) {
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        'paid' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
        'fulfilled' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
        'cancelled' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
        'refunded' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    };
    $shippingAddress = $order->shipping_address_json ?? [];
    $billingAddress = $order->billing_address_json ?? [];
    $sameAsShipping = $shippingAddress === $billingAddress;
@endphp

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
        ['label' => 'Orders', 'url' => route('storefront.account.orders.index')],
        ['label' => $order->order_number, 'url' => null],
    ]" />

    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order {{ $order->order_number }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Placed on {{ $order->placed_at?->format('F j, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $statusVariant($order->status->value) }}">{{ ucfirst($order->status->value) }}</span>
            <span class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ ucfirst(str_replace('_', ' ', $order->fulfillment_status->value)) }}</span>
        </div>
    </div>

    <div class="mt-8 rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Items</h2>
        <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-900">
            @foreach ($order->lines as $line)
                <li class="flex items-center gap-4 py-3">
                    @php $thumb = $line->variant?->product?->media->sortBy('position')->first(); @endphp
                    <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @if ($thumb)
                            <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }} &times; {{ $line->quantity }}</p>
                        @if ($line->sku_snapshot)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">SKU: {{ $line->sku_snapshot }}</p>
                        @endif
                    </div>
                    <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Shipping Address</h3>
            <address class="mt-2 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br />
                {{ $shippingAddress['address1'] ?? '' }}<br />
                {{ $shippingAddress['postal_code'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}<br />
                {{ $shippingAddress['country'] ?? '' }}
            </address>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Billing Address</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                @if ($sameAsShipping)
                    Same as shipping
                @else
                    {{ $billingAddress['address1'] ?? '' }}, {{ $billingAddress['city'] ?? '' }}
                @endif
            </p>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Payment</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ ucwords(str_replace('_', ' ', $order->payment_method->value)) }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Shipping</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></dd>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-green-600 dark:text-green-400">
                    <dt>Discount</dt>
                    <dd>-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</dd>
                </div>
            @endif
            <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                <dt>Total</dt>
                <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd>
            </div>
        </dl>
    </div>

    @if ($order->fulfillments->isNotEmpty())
        <div class="mt-6 rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Fulfillment</h3>
            @foreach ($order->fulfillments as $fulfillment)
                <div wire:key="fulfillment-{{ $fulfillment->id }}" class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <p>Shipped via {{ $fulfillment->tracking_company ?? 'carrier' }}@if($fulfillment->tracking_number) - {{ $fulfillment->tracking_number }}@endif</p>
                    @if ($fulfillment->tracking_url)
                        <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400">Track shipment &rarr;</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
