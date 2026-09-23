
@php
    $orderNumber = data_get($order, 'number', data_get($order, 'order_number', data_get($order, 'id', '')));
    $orderCurrency = data_get($currentStore ?? null, 'currency', 'EUR');
    $orderLines = data_get($order, 'lines', data_get($order, 'items', []));
    $paymentMethod = data_get($order, 'payment_method', 'credit_card');
    $shippingAddress = data_get($order, 'shipping_address', []);
    $orderSubtotal = (int) data_get($order, 'subtotal_amount', 0);
    $orderShipping = (int) data_get($order, 'shipping_amount', 0);
    $orderTax = (int) data_get($order, 'tax_amount', 0);
    $orderTotal = (int) data_get($order, 'total_amount', $orderSubtotal + $orderShipping + $orderTax);
@endphp

@section('title', 'Order confirmation · '.$orderNumber)
<div>
<article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-16">
        <header class="text-center"><span class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">@include('storefront.components.icon', ['name' => 'check', 'class' => 'size-8'])</span><h1 class="mt-5 text-3xl font-semibold tracking-tight sm:text-4xl">Thank you for your order!</h1><p class="mt-2 text-base text-stone-600 dark:text-stone-300">Order #{{ $orderNumber }}</p><p class="mt-2 text-sm text-stone-500 dark:text-stone-400">We’ve sent a confirmation to {{ data_get($order, 'email', '') }}.</p></header>

        <section aria-labelledby="confirmation-items-heading" class="mt-9 rounded-3xl border border-stone-200 p-5 sm:p-7 dark:border-stone-800"><h2 id="confirmation-items-heading" class="text-lg font-semibold">Order summary</h2><ul class="mt-5 divide-y divide-stone-200 dark:divide-stone-800">@foreach ($orderLines as $line)
            @php($lineTitle = data_get($line, 'title', data_get($line, 'product.title', 'Item')))
            @php($lineImage = data_get($line, 'image_url', data_get($line, 'product.media.0.url')))
            @php($lineQuantity = (int) data_get($line, 'quantity', 1))
            @php($lineTotal = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', data_get($line, 'price_amount', 0) * $lineQuantity)))
            <li class="flex items-center gap-3 py-4 first:pt-0 last:pb-0"><div class="size-14 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-900">@if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-6 text-stone-400'])</span>@endif</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $lineTitle }} × {{ $lineQuantity }}</p><p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', '') }}</p></div><span class="shrink-0 text-sm font-medium">@include('storefront.components.price', ['amount' => $lineTotal, 'currency' => $orderCurrency])</span></li>
        @endforeach</ul></section>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <section class="rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 class="text-sm font-semibold">Shipping address</h2><address class="mt-3 text-sm not-italic leading-6 text-stone-600 dark:text-stone-300">{{ data_get($shippingAddress, 'first_name') }} {{ data_get($shippingAddress, 'last_name') }}<br>{{ data_get($shippingAddress, 'address_line_1') }}@if(data_get($shippingAddress, 'address_line_2'))<br>{{ data_get($shippingAddress, 'address_line_2') }}@endif<br>{{ data_get($shippingAddress, 'city') }}, {{ data_get($shippingAddress, 'state') }} {{ data_get($shippingAddress, 'postal_code') }}<br>{{ data_get($shippingAddress, 'country') }}</address></section>
            <section class="rounded-2xl border border-stone-200 p-5 dark:border-stone-800"><h2 class="text-sm font-semibold">Payment method</h2><p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">@if ($paymentMethod === 'credit_card') Credit card ending in {{ data_get($order, 'payment_last_four', '••••') }} @elseif ($paymentMethod === 'bank_transfer') Bank transfer @else PayPal @endif</p></section>
        </div>

        @if ($paymentMethod === 'bank_transfer')
            <section aria-labelledby="bank-transfer-heading" class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-5 sm:p-6 dark:border-blue-900 dark:bg-blue-950/40"><div class="flex items-start gap-3">@include('storefront.components.icon', ['name' => 'alert', 'class' => 'mt-0.5 size-5 shrink-0 text-blue-800 dark:text-blue-200'])<div><h2 id="bank-transfer-heading" class="font-semibold text-blue-950 dark:text-blue-100">Bank transfer instructions</h2><p class="mt-2 text-sm leading-6 text-blue-950/80 dark:text-blue-100/80">Please transfer the total amount to the following account within 7 days. Your order will be processed once payment is confirmed.</p><dl class="mt-4 grid grid-cols-[6rem_1fr] gap-x-4 gap-y-2 text-sm text-blue-950 dark:text-blue-100"><dt class="font-semibold">Bank</dt><dd>Mock Bank AG</dd><dt class="font-semibold">IBAN</dt><dd class="break-all">DE89 3704 0044 0532 0130 00</dd><dt class="font-semibold">BIC</dt><dd>COBADEFFXXX</dd><dt class="font-semibold">Amount</dt><dd>@include('storefront.components.price', ['amount' => $orderTotal, 'currency' => $orderCurrency])</dd><dt class="font-semibold">Reference</dt><dd>#{{ $orderNumber }}</dd></dl></div></div></section>
        @endif

        <section aria-labelledby="confirmation-totals-heading" class="mt-5 rounded-2xl bg-stone-50 p-5 sm:ml-auto sm:max-w-sm sm:p-6 dark:bg-stone-900"><h2 id="confirmation-totals-heading" class="sr-only">Order totals</h2><dl class="grid gap-3 text-sm"><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $orderSubtotal, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Shipping</dt><dd>@include('storefront.components.price', ['amount' => $orderShipping, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Tax</dt><dd>@include('storefront.components.price', ['amount' => $orderTax, 'currency' => $orderCurrency])</dd></div><div class="flex justify-between gap-4 border-t border-stone-200 pt-4 text-base font-semibold dark:border-stone-800"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $orderTotal, 'currency' => $orderCurrency])</dd></div></dl></section>
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row"><a href="/" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-blue-700 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-blue-400 dark:text-stone-950">Continue shopping</a>@if (auth('customer')->check())<a href="{{ url('/account/orders/'.data_get($order, 'number', data_get($order, 'order_number', $orderNumber))) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-stone-300 px-6 text-sm font-semibold hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:border-stone-700 dark:hover:bg-stone-900">View order</a>@endif</div>
    </article>
</div>
