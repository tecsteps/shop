@extends('storefront.layout')

@section('title', $product->title.' | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section class="grid gap-8 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $product->title }}</h1>

            @if (is_string($product->vendor) && $product->vendor !== '')
                <p class="mt-2 text-sm uppercase tracking-wide text-slate-500">{{ $product->vendor }}</p>
            @endif

            @if (is_string($product->description_html) && $product->description_html !== '')
                <div class="prose prose-slate mt-5 max-w-none text-sm">
                    {!! $product->description_html !!}
                </div>
            @endif

            <div class="mt-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Available Variants</h2>

                @if ($product->variants->isEmpty())
                    <p class="mt-3 text-sm text-slate-600">No active variants are available for this product.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($product->variants as $variant)
                            <li class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <span>{{ $variant->sku ?: 'Variant '.$loop->iteration }}</span>
                                <span class="font-medium text-slate-800">
                                    {{ number_format(((int) $variant->price_amount) / 100, 2, '.', ',') }} {{ strtoupper((string) $variant->currency) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-semibold text-slate-900">Add to Cart</h2>

                @if ($errors->has('cart') || $errors->has('variant_id') || $errors->has('quantity'))
                    <div class="mt-3 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="space-y-1">
                            @foreach ($errors->only(['cart', 'variant_id', 'quantity']) as $messages)
                                @foreach ((array) $messages as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($product->variants->isEmpty())
                    <p class="mt-3 text-sm text-slate-600">This product cannot be added to the cart right now.</p>
                @else
                    <form method="POST" action="{{ route('storefront.cart.lines.store') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="variant_id" class="block text-sm font-medium text-slate-700">Variant</label>
                            <select id="variant_id" name="variant_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900">
                                @foreach ($product->variants as $variant)
                                    <option value="{{ $variant->id }}" @selected((int) old('variant_id', $product->variants->first()?->id) === (int) $variant->id)>
                                        {{ $variant->sku ?: 'Variant '.$loop->iteration }} - {{ number_format(((int) $variant->price_amount) / 100, 2, '.', ',') }} {{ strtoupper((string) $variant->currency) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('variant_id')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                            <input
                                id="quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                max="9999"
                                value="{{ old('quantity', 1) }}"
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900"
                            />
                            @error('quantity')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Add to cart
                            </button>
                            <a href="{{ route('storefront.cart.show') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">View cart</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Collections</h2>

            @if ($product->collections->isEmpty())
                <p class="mt-3 text-sm text-slate-600">This product is not currently assigned to an active collection.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($product->collections as $collection)
                        <li>
                            <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                {{ $collection->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Handle</p>
                <p class="mt-1 font-mono text-sm text-slate-800">{{ $product->handle }}</p>
            </div>
        </div>
    </section>
@endsection
