@extends('storefront.layout')

@section('title', 'Cart | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Shopping Cart</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $cart)
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                Your cart is currently empty.
            </p>
        @elseif ($cart->lines->isEmpty())
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                Your cart has no line items yet.
            </p>
        @else
            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div class="space-y-3 lg:col-span-2">
                    @foreach ($cart->lines as $line)
                        @php($variant = $line->variant)
                        @php($product = $variant?->product)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    @if ($product)
                                        <a href="{{ route('storefront.products.show', $product->handle) }}" class="font-semibold text-slate-900 hover:text-slate-700">
                                            {{ $product->title }}
                                        </a>
                                    @else
                                        <p class="font-semibold text-slate-900">Unavailable product</p>
                                    @endif

                                    <p class="mt-1 text-sm text-slate-600">Quantity: {{ (int) $line->quantity }}</p>
                                    @if (is_string($variant?->sku) && $variant->sku !== '')
                                        <p class="text-xs text-slate-500">SKU: {{ $variant->sku }}</p>
                                    @endif
                                </div>
                                <div class="text-right text-sm font-medium text-slate-800">
                                    <p>{{ number_format(((int) $line->line_total_amount) / 100, 2, '.', ',') }} {{ strtoupper((string) $cart->currency) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-200 pt-4">
                                <form method="POST" action="{{ route('storefront.cart.lines.update', ['lineId' => $line->id]) }}" class="flex items-end gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="cart_version" value="{{ (int) $cart->cart_version }}" />
                                    <div>
                                        <label for="quantity-{{ $line->id }}" class="block text-xs font-medium text-slate-600">Quantity</label>
                                        <input
                                            id="quantity-{{ $line->id }}"
                                            name="quantity"
                                            type="number"
                                            min="1"
                                            max="9999"
                                            value="{{ (int) $line->quantity }}"
                                            class="mt-1 w-24 rounded-md border border-slate-300 px-2 py-1.5 text-sm text-slate-900"
                                        />
                                    </div>
                                    <button type="submit" class="inline-flex h-9 items-center rounded-md border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                        Update
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('storefront.cart.lines.destroy', ['lineId' => $line->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="cart_version" value="{{ (int) $cart->cart_version }}" />
                                    <button type="submit" class="inline-flex h-9 items-center rounded-md border border-rose-200 px-3 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Summary</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Items</dt>
                            <dd>{{ $itemCount }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Subtotal</dt>
                            <dd>{{ number_format($subtotal / 100, 2, '.', ',') }} {{ strtoupper((string) $cart->currency) }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Discounts</dt>
                            <dd>-{{ number_format($discount / 100, 2, '.', ',') }} {{ strtoupper((string) $cart->currency) }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-semibold text-slate-900">
                            <dt>Total</dt>
                            <dd>{{ number_format($total / 100, 2, '.', ',') }} {{ strtoupper((string) $cart->currency) }}</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ route('storefront.cart.checkout.start') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Start checkout
                        </button>
                    </form>
                </aside>
            </div>
        @endif
    </section>
@endsection
