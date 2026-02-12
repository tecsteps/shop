@extends('storefront.layout')

@section('content')
<section class="grid gap-8 lg:grid-cols-2">
    <div>
        @php
            $mainImage = $product->media->first();
            $price = $selectedVariant->price_amount;
            $currencyCode = $selectedVariant->currency ?: $currency;
            $inventory = $selectedVariant->inventoryItem;
            $available = $inventory ? ($inventory->quantity_on_hand - $inventory->quantity_reserved) : null;
            $optionSummary = $selectedVariant->optionValues
                ->map(fn ($value) => $value->option->name.': '.$value->value)
                ->implode(' / ');
        @endphp

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            @if ($mainImage)
                <img src="{{ $mainImage->storage_key }}" alt="{{ $mainImage->alt_text ?: $product->title }}" class="h-full w-full object-cover">
            @else
                <div class="flex aspect-square items-center justify-center bg-zinc-100 text-zinc-400">
                    <span class="text-sm font-medium">No image available</span>
                </div>
            @endif
        </div>

        @if ($product->media->count() > 1)
            <div class="mt-3 grid grid-cols-4 gap-3">
                @foreach ($product->media as $media)
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <img src="{{ $media->storage_key }}" alt="{{ $media->alt_text ?: $product->title }}" class="h-20 w-full object-cover">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <nav class="text-xs font-medium uppercase tracking-wide text-zinc-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-zinc-900">Home</a>
            @if ($product->collections->isNotEmpty())
                <span class="mx-2">/</span>
                <a href="{{ route('storefront.collections.show', $product->collections->first()->handle) }}" class="hover:text-zinc-900">{{ $product->collections->first()->title }}</a>
            @endif
            <span class="mx-2">/</span>
            <span class="text-zinc-700">{{ $product->title }}</span>
        </nav>

        <header class="space-y-2">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">{{ $product->title }}</h1>
            <p class="text-2xl font-semibold text-zinc-900" aria-live="polite">{{ number_format($price / 100, 2, '.', ',') }} {{ $currencyCode }}</p>
            @if ($selectedVariant->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                <p class="text-sm text-zinc-500 line-through">{{ number_format($selectedVariant->compare_at_amount / 100, 2, '.', ',') }} {{ $currencyCode }}</p>
            @endif
            @if ($optionSummary !== '')
                <p class="text-sm text-zinc-600">Selected variant: {{ $optionSummary }}</p>
            @endif
        </header>

        <form method="GET" action="{{ route('storefront.products.show', $product->handle) }}" class="space-y-2">
            <label for="variant" class="block text-sm font-medium text-zinc-700">Choose variant</label>
            <select id="variant" name="variant" class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" onchange="this.form.submit()">
                @foreach ($product->variants as $variant)
                    @php
                        $variantLabel = $variant->optionValues
                            ->map(fn ($value) => $value->option->name.': '.$value->value)
                            ->implode(' / ');
                    @endphp
                    <option value="{{ $variant->id }}" @selected($selectedVariant->id === $variant->id)>
                        {{ $variantLabel !== '' ? $variantLabel : 'Default' }} - {{ number_format($variant->price_amount / 100, 2, '.', ',') }} {{ $variant->currency }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm">
            <h2 class="text-sm font-semibold text-zinc-900">Stock status</h2>
            @if ($available === null)
                <p class="mt-2 text-zinc-600">Availability is updated at checkout.</p>
            @elseif ($available > 10)
                <p class="mt-2 text-emerald-700" aria-live="polite">In stock</p>
            @elseif ($available > 0)
                <p class="mt-2 text-amber-700" aria-live="polite">Only {{ $available }} left in stock</p>
            @elseif ($selectedVariant->inventoryItem && $selectedVariant->inventoryItem->policy->value === 'continue')
                <p class="mt-2 text-blue-700" aria-live="polite">Available on backorder</p>
            @else
                <p class="mt-2 text-rose-700" aria-live="polite">Out of stock</p>
            @endif
        </div>

        <form method="POST" action="{{ route('storefront.cart.lines.add') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="variant_id" value="{{ $selectedVariant->id }}">
            <div>
                <label for="quantity" class="block text-sm font-medium text-zinc-700">Quantity</label>
                <input id="quantity" type="number" name="quantity" min="1" value="1" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700" @disabled($available !== null && $available <= 0 && $selectedVariant->inventoryItem?->policy?->value !== 'continue')>
                {{ $available !== null && $available <= 0 && $selectedVariant->inventoryItem?->policy?->value !== 'continue' ? 'Sold out' : 'Add to cart' }}
            </button>
        </form>

        @if ($product->description_html)
            <div class="prose prose-sm max-w-none border-t border-zinc-200 pt-6 text-zinc-700">
                <h2 class="text-base font-semibold text-zinc-900">Product details</h2>
                {!! $product->description_html !!}
            </div>
        @endif
    </div>
</section>

@if ($relatedProducts->isNotEmpty())
    <section class="mt-14">
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900">Related products</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($relatedProducts as $relatedProduct)
                @include('storefront._product-card', ['product' => $relatedProduct])
            @endforeach
        </div>
    </section>
@endif
@endsection
