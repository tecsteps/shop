
@php
    $cartLines = data_get($cart, 'lines', data_get($cart, 'items', []));
    $cartCurrency = data_get($currentStore ?? null, 'currency', 'EUR');
    $cartSubtotal = (int) data_get($cart, 'subtotal_amount', data_get($cart, 'subtotal', 0));
    $cartDiscount = (int) data_get($cart, 'discount_amount', 0);
    $cartTotal = (int) data_get($cart, 'total_amount', $cartSubtotal - $cartDiscount);
@endphp

@section('title', 'Your cart')
<div>
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Your Cart']]])
        <div class="mt-7 flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-800 dark:text-blue-300">Review your picks</p><h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Your Cart</h1></div><span class="text-sm text-stone-600 dark:text-stone-300">{{ count($cartLines) }} {{ \Illuminate\Support\Str::plural('item', count($cartLines)) }}</span></div>

        @if (count($cartLines))
            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                <div>
                    <div class="hidden overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800 md:block">
                        <table class="w-full text-left">
                            <thead class="bg-stone-50 text-xs uppercase tracking-[0.12em] text-stone-500 dark:bg-stone-900 dark:text-stone-400"><tr><th scope="col" class="px-5 py-4">Product</th><th scope="col" class="px-4 py-4">Price</th><th scope="col" class="px-4 py-4">Quantity</th><th scope="col" class="px-4 py-4 text-right">Total</th><th scope="col" class="px-3 py-4"><span class="sr-only">Remove</span></th></tr></thead>
                            <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                                @foreach ($cartLines as $line)
                                    @php
                                        $lineId = data_get($line, 'id', $loop->index);
                                        $lineProduct = data_get($line, 'product', data_get($line, 'variant.product'));
                                        $lineTitle = data_get($line, 'title', data_get($lineProduct, 'title', 'Item'));
                                        $lineImage = data_get($line, 'image_url', data_get($lineProduct, 'media.0.url', data_get($lineProduct, 'image_url')));
                                        $lineQuantity = (int) data_get($line, 'quantity', 1);
                                        $unitPrice = (int) data_get($line, 'price_amount', 0);
                                        $lineTotal = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', $unitPrice * $lineQuantity));
                                    @endphp
                                    <tr wire:key="cart-row-{{ $lineId }}">
                                        <th scope="row" class="px-5 py-5 font-normal"><div class="flex min-w-0 items-center gap-4"><div class="size-20 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-900">@if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center text-stone-300">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-7'])</span>@endif</div><div class="min-w-0"><a href="{{ url('/products/'.data_get($lineProduct, 'handle', '')) }}" class="font-semibold hover:underline">{{ $lineTitle }}</a><p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', '') }}</p></div></div></th>
                                        <td class="whitespace-nowrap px-4 py-5 text-sm">@include('storefront.components.price', ['amount' => $unitPrice, 'currency' => $cartCurrency])</td>
                                        <td class="px-4 py-5">@include('storefront.components.quantity-selector', ['value' => $lineQuantity, 'min' => 1, 'wireModel' => 'quantities.'.$lineId, 'compact' => true, 'decreaseAction' => 'decreaseQuantity('.$lineId.')', 'increaseAction' => 'increaseQuantity('.$lineId.')'])</td>
                                        <td class="whitespace-nowrap px-4 py-5 text-right text-sm font-semibold">@include('storefront.components.price', ['amount' => $lineTotal, 'currency' => $cartCurrency])</td>
                                        <td class="px-3 py-5 text-right"><button type="button" wire:click="removeItem({{ $lineId }})" class="inline-flex size-11 items-center justify-center rounded-full text-stone-500 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-rose-700 dark:text-stone-400 dark:hover:bg-rose-950/50 dark:hover:text-rose-300" aria-label="Remove {{ $lineTitle }} from cart">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-4'])</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <ul class="grid gap-4 md:hidden" aria-label="Cart items">
                        @foreach ($cartLines as $line)
                            @php($lineId = data_get($line, 'id', $loop->index))
                            @php($lineProduct = data_get($line, 'product', data_get($line, 'variant.product')))
                            @php($lineTitle = data_get($line, 'title', data_get($lineProduct, 'title', 'Item')))
                            @php($lineImage = data_get($line, 'image_url', data_get($lineProduct, 'media.0.url', data_get($lineProduct, 'image_url'))))
                            @php($lineQuantity = (int) data_get($line, 'quantity', 1))
                            @php($lineTotal = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', data_get($line, 'price_amount', 0) * $lineQuantity)))
                            <li wire:key="cart-card-{{ $lineId }}" class="rounded-2xl border border-stone-200 p-4 dark:border-stone-800"><div class="flex gap-3"><div class="size-20 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-900">@if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-7 text-stone-400'])</span>@endif</div><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><a href="{{ url('/products/'.data_get($lineProduct, 'handle', '')) }}" class="font-semibold hover:underline">{{ $lineTitle }}</a><p class="mt-1 truncate text-sm text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', '') }}</p></div><button type="button" wire:click="removeItem({{ $lineId }})" class="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-stone-500 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-rose-700 dark:text-stone-400 dark:hover:bg-rose-950/50" aria-label="Remove {{ $lineTitle }} from cart">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-4'])</button></div><div class="mt-4 flex items-center justify-between gap-3">@include('storefront.components.quantity-selector', ['value' => $lineQuantity, 'min' => 1, 'wireModel' => 'quantities.'.$lineId, 'compact' => true, 'decreaseAction' => 'decreaseQuantity('.$lineId.')', 'increaseAction' => 'increaseQuantity('.$lineId.')'])<span class="text-sm font-semibold">@include('storefront.components.price', ['amount' => $lineTotal, 'currency' => $cartCurrency])</span></div></div></div></li>
                        @endforeach
                    </ul>
                </div>

                <aside aria-labelledby="cart-totals-title" class="rounded-3xl bg-stone-50 p-5 sm:p-6 dark:bg-stone-900">
                    <h2 id="cart-totals-title" class="text-lg font-semibold">Order summary</h2>
                    @if (data_get($cart, 'discount_code'))
                        <div class="mt-5 flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"><span>{{ data_get($cart, 'discount_code') }} applied</span><button type="button" wire:click="removeDiscount" class="min-h-10 px-2 font-semibold underline underline-offset-4">Remove</button></div>
                    @else
                        <form wire:submit="applyDiscount" class="mt-5 flex gap-2"><label for="cart-page-discount" class="sr-only">Discount code</label><input id="cart-page-discount" type="text" wire:model="discountCode" placeholder="Discount code" class="storefront-input min-w-0 flex-1"><button type="submit" class="min-h-11 rounded-xl border border-stone-300 px-4 text-sm font-semibold hover:bg-white dark:border-stone-700 dark:hover:bg-stone-800"><span wire:loading.remove wire:target="applyDiscount">Apply</span><span wire:loading wire:target="applyDiscount">Applying…</span></button></form>
                    @endif
                    @error('discountCode')<p class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                    <dl aria-live="polite" class="mt-5 grid gap-3 border-t border-stone-200 pt-5 text-sm dark:border-stone-800"><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $cartSubtotal, 'currency' => $cartCurrency])</dd></div>@if ($cartDiscount > 0)<div class="flex justify-between gap-4 text-emerald-800 dark:text-emerald-300"><dt>Discount</dt><dd>−@include('storefront.components.price', ['amount' => $cartDiscount, 'currency' => $cartCurrency])</dd></div>@endif<div class="flex justify-between gap-4 border-t border-stone-200 pt-4 text-base font-semibold dark:border-stone-800"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $cartTotal, 'currency' => $cartCurrency])</dd></div></dl>
                    <a href="{{ data_get($cart, 'checkout_id') ? url('/checkout/'.data_get($cart, 'checkout_id')) : '/checkout' }}" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-blue-400 dark:text-stone-950 dark:hover:bg-blue-300">Checkout</a>
                    <a href="/collections" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">Continue shopping</a>
                    <p class="mt-4 text-center text-xs leading-5 text-stone-500 dark:text-stone-400">Shipping and taxes are calculated at checkout.</p>
                </aside>
            </div>
        @else
            <div class="mx-auto flex min-h-[26rem] max-w-xl flex-col items-center justify-center px-5 text-center">
                <span class="flex size-16 items-center justify-center rounded-full bg-stone-100 text-stone-400 dark:bg-stone-900 dark:text-stone-500">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-8'])</span>
                <h2 class="mt-5 text-xl font-semibold">Your cart is empty</h2><p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-300">Your next favorite thing is waiting to be found.</p><a href="/collections" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-white dark:text-stone-950">Continue shopping</a>
            </div>
        @endif
    </section>
</div>
