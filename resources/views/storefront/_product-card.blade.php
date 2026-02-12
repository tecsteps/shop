@php
    $primaryImage = $product->media->first();
    $variant = $product->defaultVariant ?? $product->variants->first();
    $currencyCode = $variant?->currency ?: ($currency ?? $store->default_currency);
    $available = $variant?->inventoryItem
        ? ($variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved)
        : null;
    $isBackorderable = $variant?->inventoryItem?->policy?->value === 'continue';
    $isSoldOut = $available !== null && $available <= 0 && ! $isBackorderable;
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" aria-label="Product card: {{ $product->title }}">
    <a href="{{ route('storefront.products.show', $product->handle) }}" class="block aspect-square overflow-hidden bg-zinc-100">
        @if ($primaryImage)
            <img src="{{ $primaryImage->storage_key }}" alt="{{ $primaryImage->alt_text ?: $product->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex h-full items-center justify-center text-zinc-400">
                <span class="text-sm font-medium">No image</span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-3 p-4">
        <div>
            <h3 class="text-base font-semibold leading-tight text-zinc-900">
                <a href="{{ route('storefront.products.show', $product->handle) }}" class="hover:underline">{{ $product->title }}</a>
            </h3>
            <p class="mt-1 text-sm text-zinc-600">{{ number_format(($variant?->price_amount ?? 0) / 100, 2, '.', ',') }} {{ $currencyCode }}</p>
        </div>

        <div class="mt-auto space-y-2">
            @if ($available !== null)
                <p class="text-xs {{ $available > 0 ? 'text-emerald-700' : ($isBackorderable ? 'text-blue-700' : 'text-rose-700') }}" aria-live="polite">
                    {{ $available > 0 ? 'In stock' : ($isBackorderable ? 'Available on backorder' : 'Sold out') }}
                </p>
            @endif

            @if ($variant)
                <form method="POST" action="{{ route('storefront.cart.lines.add') }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                    <label class="block text-xs font-medium text-zinc-700" for="quantity-{{ $variant->id }}">Quantity</label>
                    <input id="quantity-{{ $variant->id }}" type="number" name="quantity" min="1" value="1" class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <button type="submit" class="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700" @disabled($isSoldOut)>
                        {{ $isSoldOut ? 'Sold out' : 'Add to cart' }}
                    </button>
                </form>
            @else
                <a href="{{ route('storefront.products.show', $product->handle) }}" class="inline-flex w-full items-center justify-center rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                    View details
                </a>
            @endif
        </div>
    </div>
</article>
