@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Your Cart</h1>
        <p class="mt-2 text-sm text-zinc-600">Review items, update quantities, and proceed to checkout.</p>
    </header>

    @if (! $cart || $cart->lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center">
            <h2 class="text-lg font-semibold text-zinc-900">Your cart is empty</h2>
            <p class="mt-2 text-sm text-zinc-600">Add an item from a collection or product page to get started.</p>
            <a href="{{ route('storefront.collections.index') }}" class="mt-6 inline-flex rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Continue shopping</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <h2 class="sr-only">Cart line items</h2>
                <div class="hidden grid-cols-[1.5fr_0.7fr_0.6fr_0.7fr_0.3fr] border-b border-zinc-200 bg-zinc-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 md:grid">
                    <span>Product</span>
                    <span>Price</span>
                    <span>Quantity</span>
                    <span>Total</span>
                    <span class="sr-only">Remove</span>
                </div>

                <div class="divide-y divide-zinc-200">
                    @foreach ($cart->lines as $line)
                        @php
                            $variant = $line->variant;
                            $product = $variant->product;
                            $variantLabel = $variant->optionValues->map(fn ($value) => $value->option->name.': '.$value->value)->implode(' / ');
                            $currencyCode = $variant->currency ?: $currency;
                        @endphp

                        <div class="grid gap-3 px-4 py-4 md:grid-cols-[1.5fr_0.7fr_0.6fr_0.7fr_0.3fr] md:items-center">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900">
                                    <a href="{{ route('storefront.products.show', $product->handle) }}" class="hover:underline">{{ $product->title }}</a>
                                </h3>
                                @if ($variantLabel !== '')
                                    <p class="mt-1 text-xs text-zinc-600">{{ $variantLabel }}</p>
                                @endif
                            </div>

                            <p class="text-sm text-zinc-700">{{ number_format($line->unit_price_amount / 100, 2, '.', ',') }} {{ $currencyCode }}</p>

                            <form method="POST" action="{{ route('storefront.cart.lines.update', ['lineId' => $line->id]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="qty-{{ $line->id }}" class="sr-only">Quantity for {{ $product->title }}</label>
                                <input id="qty-{{ $line->id }}" type="number" name="quantity" min="0" value="{{ $line->quantity }}" class="w-20 rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                <button type="submit" class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-100">Update</button>
                            </form>

                            <p class="text-sm font-semibold text-zinc-900">{{ number_format($line->line_total_amount / 100, 2, '.', ',') }} {{ $currencyCode }}</p>

                            <form method="POST" action="{{ route('storefront.cart.lines.remove', ['lineId' => $line->id]) }}" class="text-right">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-rose-700 hover:underline" aria-label="Remove {{ $product->title }} from cart">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-zinc-900">Order summary</h2>

                @if ($appliedDiscount)
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        <p class="font-semibold">Discount code: {{ strtoupper($discountCode) }}</p>
                        <form method="POST" action="{{ route('storefront.cart.discount.remove') }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold underline">Remove discount</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('storefront.cart.discount.apply') }}" class="space-y-2">
                        @csrf
                        <label for="discount_code" class="block text-sm font-medium text-zinc-700">Discount code</label>
                        <div class="flex gap-2">
                            <input id="discount_code" type="text" name="discount_code" value="{{ old('discount_code', $discountCode) }}" placeholder="Enter code" class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">Apply</button>
                        </div>
                    </form>
                @endif

                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600">Subtotal</dt>
                        <dd class="font-medium text-zinc-900">{{ number_format($totals['subtotal'] / 100, 2, '.', ',') }} {{ $totals['currency'] }}</dd>
                    </div>
                    @if ($totals['discount'] > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-600">Discount</dt>
                            <dd class="font-medium text-emerald-700">-{{ number_format($totals['discount'] / 100, 2, '.', ',') }} {{ $totals['currency'] }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-zinc-200 pt-2">
                        <dt class="text-base font-semibold text-zinc-900">Estimated total</dt>
                        <dd class="text-base font-semibold text-zinc-900">{{ number_format($totals['total'] / 100, 2, '.', ',') }} {{ $totals['currency'] }}</dd>
                    </div>
                </dl>

                <p class="text-xs text-zinc-500">Shipping and taxes are calculated during checkout.</p>

                <form method="POST" action="{{ route('storefront.checkout.create') }}" class="space-y-2">
                    @csrf
                    <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700">Proceed to checkout</button>
                </form>

                <a href="{{ route('storefront.collections.index') }}" class="block text-center text-sm font-medium text-zinc-600 hover:text-zinc-900">Continue shopping</a>
            </aside>
        </div>
    @endif
</section>
@endsection
