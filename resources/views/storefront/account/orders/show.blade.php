
@php
    $orderNumber = data_get($order, 'number', data_get($order, 'order_number', data_get($order, 'id', '')));
    $orderCurrency = data_get($currentStore ?? null, 'currency', 'EUR');
    $orderLines = data_get($order, 'lines', data_get($order, 'items', []));
    $orderSubtotal = (int) data_get($order, 'subtotal_amount', 0);
    $orderDiscount = (int) data_get($order, 'discount_amount', 0);
    $orderShipping = (int) data_get($order, 'shipping_amount', 0);
    $orderTax = (int) data_get($order, 'tax_amount', 0);
    $orderTotal = (int) data_get($order, 'total_amount', $orderSubtotal - $orderDiscount + $orderShipping + $orderTax);
@endphp

@section('title', 'Order #'.$orderNumber)
<div>
<article class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [['label' => 'My account', 'url' => '/account'], ['label' => 'Orders', 'url' => '/account/orders'], ['label' => '#'.$orderNumber]]])
        <header class="mt-7 flex flex-col gap-4 border-b border-stone-200 pb-6 sm:flex-row sm:items-end sm:justify-between dark:border-stone-800"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-800 dark:text-blue-300">Order details</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Order #{{ $orderNumber }}</h1><p class="mt-2 text-sm text-stone-600 dark:text-stone-300">Placed {{ data_get($order, 'created_at')?->format('F j, Y') }}</p></div><div class="flex flex-wrap gap-2">@include('storefront.components.badge', ['text' => \Illuminate\Support\Str::headline(data_get($order, 'status', 'pending')), 'variant' => strtolower(data_get($order, 'status', 'pending'))])@if(data_get($order, 'fulfillment_status'))@include('storefront.components.badge', ['text' => \Illuminate\Support\Str::headline(data_get($order, 'fulfillment_status')), 'variant' => 'fulfilled'])@endif</div></header>

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <section aria-labelledby="order-items-heading"><h2 id="order-items-heading" class="text-lg font-semibold">Items</h2><ul class="mt-3 divide-y divide-stone-200 rounded-2xl border border-stone-200 px-4 dark:divide-stone-800 dark:border-stone-800">@foreach ($orderLines as $line)
                    @php($lineTitle = data_get($line, 'title', data_get($line, 'product.title', 'Item')))
                    @php($lineImage = data_get($line, 'image_url', data_get($line, 'product.media.0.url')))
                    @php($lineQuantity = (int) data_get($line, 'quantity', 1))
                    @php($lineAmount = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', data_get($line, 'price_amount', 0) * $lineQuantity)))
                    <li class="flex items-center gap-4 py-4"><div class="size-16 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-900">@if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-7 text-stone-400'])</span>@endif</div><div class="min-w-0 flex-1"><p class="font-medium">{{ $lineTitle }} <span class="text-stone-500">× {{ $lineQuantity }}</span></p><p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', '') }}</p></div><span class="shrink-0 text-sm font-semibold">@include('storefront.components.price', ['amount' => $lineAmount, 'currency' => $orderCurrency])</span></li>
                @endforeach</ul></section>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    <section class="rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 class="text-sm font-semibold">Shipping address</h2><address class="mt-3 text-sm not-italic leading-6 text-stone-600 dark:text-stone-300">{{ data_get($order, 'shipping_address.first_name') }} {{ data_get($order, 'shipping_address.last_name') }}<br>{{ data_get($order, 'shipping_address.address_line_1') }}<br>{{ data_get($order, 'shipping_address.city') }}, {{ data_get($order, 'shipping_address.postal_code') }}<br>{{ data_get($order, 'shipping_address.country') }}</address></section>
                    <section class="rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 class="text-sm font-semibold">Billing address</h2>@if(data_get($order, 'billing_same_as_shipping', false))<p class="mt-3 text-sm text-stone-600 dark:text-stone-300">Same as shipping</p>@else<address class="mt-3 text-sm not-italic leading-6 text-stone-600 dark:text-stone-300">{{ data_get($order, 'billing_address.first_name') }} {{ data_get($order, 'billing_address.last_name') }}<br>{{ data_get($order, 'billing_address.address_line_1') }}<br>{{ data_get($order, 'billing_address.city') }}, {{ data_get($order, 'billing_address.postal_code') }}</address>@endif</section>
                    <section class="rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 class="text-sm font-semibold">Payment</h2><p class="mt-3 text-sm text-stone-600 dark:text-stone-300">@if(data_get($order, 'payment_method') === 'credit_card')Card ending in {{ data_get($order, 'payment_last_four', '••••') }}@else{{ \Illuminate\Support\Str::headline(data_get($order, 'payment_method', 'Payment')) }}@endif</p></section>
                </div>

                @if (count(data_get($order, 'fulfillments', [])))
                    <section aria-labelledby="fulfillment-heading" class="mt-6 rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 id="fulfillment-heading" class="font-semibold">Fulfillment</h2>@foreach (data_get($order, 'fulfillments', []) as $fulfillment)<p class="mt-3 text-sm text-stone-600 dark:text-stone-300">Shipped via {{ data_get($fulfillment, 'tracking_company', 'Carrier') }}@if(data_get($fulfillment, 'tracking_number')) · {{ data_get($fulfillment, 'tracking_number') }}@endif</p>@if(data_get($fulfillment, 'tracking_url'))<a href="{{ data_get($fulfillment, 'tracking_url') }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Track shipment <span class="sr-only">(opens in a new tab)</span>@include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])</a>@endif @endforeach</section>
                @endif
            </div>

            <aside class="h-fit rounded-3xl bg-stone-50 p-5 sm:p-6 lg:sticky lg:top-28 dark:bg-stone-900"><h2 class="text-lg font-semibold">Order total</h2><dl class="mt-5 grid gap-3 text-sm"><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $orderSubtotal, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Shipping</dt><dd>@include('storefront.components.price', ['amount' => $orderShipping, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Tax</dt><dd>@include('storefront.components.price', ['amount' => $orderTax, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4 text-emerald-800 dark:text-emerald-300"><dt>Discount</dt><dd>−@include('storefront.components.price', ['amount' => $orderDiscount, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4 border-t border-stone-200 pt-4 text-base font-semibold dark:border-stone-800"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $orderTotal, 'currency' => $orderCurrency])</dd></div></dl></aside>
        </div>
    </article>
</div>
