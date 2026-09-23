@php
    $cartLines = data_get($cart ?? null, 'lines', data_get($cart ?? null, 'items', []));
    $currency = data_get($currentStore ?? null, 'currency', 'EUR');
    $cartSubtotal = (int) data_get($cart ?? null, 'subtotal_amount', data_get($cart ?? null, 'subtotal', 0));
    $cartDiscount = (int) data_get($cart ?? null, 'discount_amount', 0);
    $cartTotal = (int) data_get($cart ?? null, 'total_amount', $cartSubtotal - $cartDiscount);
@endphp
<div>
    @if ($isOpen ?? false)
        <div class="fixed inset-0 z-[60] bg-stone-950/50 transition-opacity" wire:click="close" aria-hidden="true"></div>
        <aside x-trap.noscroll="true" x-on:keydown.escape.window="$wire.close()" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" class="fixed inset-y-0 right-0 z-[61] flex w-full max-w-96 flex-col bg-white shadow-2xl dark:bg-stone-950">
            <div class="flex min-h-16 items-center justify-between gap-4 border-b border-stone-200 px-5 dark:border-stone-800">
                <h2 id="cart-drawer-title" class="text-lg font-semibold">Your cart <span class="font-normal text-stone-500 dark:text-stone-400">({{ data_get($cart ?? null, 'item_count', count($cartLines)) }})</span></h2>
                <button type="button" wire:click="close" autofocus class="inline-flex size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800" aria-label="Close cart">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-5'])</button>
            </div>
            @if (count($cartLines))
                <ul class="min-h-0 flex-1 divide-y divide-stone-200 overflow-y-auto px-5 dark:divide-stone-800" aria-label="Cart items" aria-live="polite">
                    @foreach ($cartLines as $line)
                        @php
                            $lineId = data_get($line, 'id', $loop->index);
                            $lineProduct = data_get($line, 'product', data_get($line, 'variant.product'));
                            $lineTitle = data_get($line, 'title', data_get($lineProduct, 'title', 'Item'));
                            $lineImage = data_get($line, 'image_url', data_get($lineProduct, 'media.0.url', data_get($lineProduct, 'image_url')));
                            $lineQuantity = (int) data_get($line, 'quantity', 1);
                            $lineAmount = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', data_get($line, 'price_amount', 0) * $lineQuantity));
                        @endphp
                        <li wire:key="cart-line-{{ $lineId }}" class="flex gap-3 py-4">
                            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-900">
                                @if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center text-stone-300">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-7'])</span>@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><a href="{{ url('/products/'.data_get($lineProduct, 'handle', '')) }}" class="block truncate text-sm font-semibold hover:underline">{{ $lineTitle }}</a><p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', '') }}</p></div>
                                    <span class="shrink-0 text-sm font-medium">@include('storefront.components.price', ['amount' => $lineAmount, 'currency' => $currency])</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    @include('storefront.components.quantity-selector', ['value' => $lineQuantity, 'min' => 1, 'wireModel' => 'quantities.'.$lineId, 'compact' => true, 'decreaseAction' => 'decreaseQuantity('.$lineId.')', 'increaseAction' => 'increaseQuantity('.$lineId.')'])
                                    <button type="button" wire:click="removeItem({{ $lineId }})" class="min-h-11 rounded-lg px-2 text-xs font-medium text-stone-500 underline underline-offset-4 hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-rose-700 dark:text-stone-400 dark:hover:text-rose-300" aria-label="Remove {{ $lineTitle }} from cart">Remove</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-stone-200 px-5 pb-5 pt-4 dark:border-stone-800">
                    @if (data_get($cart ?? null, 'discount_code'))
                        <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"><span>{{ data_get($cart, 'discount_code') }} discount applied</span><button type="button" wire:click="removeDiscount" class="min-h-10 px-2 font-semibold underline underline-offset-4">Remove</button></div>
                    @else
                        <form wire:submit="applyDiscount" class="mb-4 flex gap-2">
                            <label for="cart-discount-code" class="sr-only">Discount code</label><input id="cart-discount-code" type="text" wire:model="discountCode" placeholder="Discount code" class="storefront-input min-w-0 flex-1">
                            <button type="submit" class="min-h-11 rounded-xl border border-stone-300 px-4 text-sm font-semibold hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800"><span wire:loading.remove wire:target="applyDiscount">Apply</span><span wire:loading wire:target="applyDiscount">Applying…</span></button>
                        </form>
                    @endif
                    @error('discountCode')<p class="mb-3 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                    <dl class="grid gap-2 text-sm"><div class="flex justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $cartSubtotal, 'currency' => $currency])</dd></div>@if ($cartDiscount > 0)<div class="flex justify-between gap-4 text-emerald-800 dark:text-emerald-300"><dt>Discount</dt><dd>−@include('storefront.components.price', ['amount' => $cartDiscount, 'currency' => $currency])</dd></div>@endif<div class="flex justify-between gap-4 border-t border-stone-200 pt-3 text-base font-semibold dark:border-stone-800"><dt>Estimated total</dt><dd>@include('storefront.components.price', ['amount' => $cartTotal, 'currency' => $currency])</dd></div></dl>
                    <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Shipping and taxes calculated at checkout.</p>
                    <a href="/checkout" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-blue-400 dark:text-stone-950 dark:hover:bg-blue-300">Continue to checkout</a>
                    <button type="button" wire:click="close" class="mt-2 inline-flex min-h-11 w-full items-center justify-center rounded-xl px-4 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800">Continue shopping</button>
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <span class="flex size-16 items-center justify-center rounded-full bg-stone-100 text-stone-400 dark:bg-stone-900 dark:text-stone-500">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-8'])</span>
                    <p class="mt-5 text-base font-semibold">Your cart is empty</p>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Find something you'll love and it will appear here.</p>
                    <button type="button" wire:click="close" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-300 px-5 text-sm font-semibold hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">Continue shopping</button>
                </div>
            @endif
        </aside>
    @endif
</div>
