{{--
    Product card used in grids (spec 04 §4.5 + §16).

    Props:
    - product: Product (required) — with variants.inventoryItem + media eager-loaded
    - headingLevel: string — heading tag for the title (default: h3)
    - showQuickAdd: bool — show quick add / choose options action (default: true)
--}}
@props([
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    /** @var \App\Models\Product $product */
    $variants = $product->variants;
    $defaultVariant = $variants->firstWhere('is_default', true) ?? $variants->first();
    $currency = $defaultVariant?->currency ?? app('current_store')->default_currency ?? 'USD';
    $minPrice = $variants->min('price_amount');
    $maxCompareAt = $variants->max('compare_at_amount');
    $onSale = $maxCompareAt !== null && $minPrice !== null && $maxCompareAt > $minPrice;
    $soldOut = $variants->isNotEmpty() && $variants->every(
        fn (\App\Models\ProductVariant $variant): bool => ! $variant->isInStock() && ! $variant->isBackorderable()
    );
    $images = $product->media->filter(
        fn (\App\Models\ProductMedia $media): bool => $media->status === \App\Enums\MediaStatus::Ready
    )->values();
    $primaryImage = $images->first();
    $hoverImage = $images->get(1);
    $productUrl = route('storefront.products.show', ['handle' => $product->handle]);
    $singleVariant = $variants->count() === 1;
@endphp

<div {{ $attributes->class('group relative flex flex-col') }}>
    <div class="relative aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
        <a href="{{ $productUrl }}" class="block size-full focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-blue-500" tabindex="-1" aria-hidden="true">
            @if ($primaryImage)
                <img src="{{ $primaryImage->url() }}"
                     alt="{{ $primaryImage->alt_text ?? $product->title }}"
                     loading="lazy"
                     class="size-full object-cover object-center transition-opacity duration-300 {{ $hoverImage ? 'group-hover:opacity-0' : '' }}">
                @if ($hoverImage)
                    <img src="{{ $hoverImage->url() }}"
                         alt=""
                         loading="lazy"
                         class="absolute inset-0 size-full object-cover object-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                @endif
            @else
                <span class="flex size-full items-center justify-center text-gray-300 dark:text-gray-600">
                    <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                    </svg>
                </span>
            @endif
        </a>
        <div class="absolute left-2 top-2 flex flex-col items-start gap-1">
            @if ($onSale)
                <x-storefront::badge text="Sale" variant="sale" />
            @endif
            @if ($soldOut)
                <x-storefront::badge text="Sold out" variant="sold-out" />
            @endif
        </div>
    </div>

    <a href="{{ $productUrl }}" class="mt-3 block focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded">
        <{{ $headingLevel }} class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
            {{ $product->title }}
        </{{ $headingLevel }}>
        <span class="mt-1 block text-sm">
            @if ($minPrice !== null)
                <x-storefront::price :amount="$minPrice" :currency="$currency" :compareAtAmount="$onSale ? $maxCompareAt : null" />
            @endif
        </span>
    </a>

    @if ($showQuickAdd && $defaultVariant)
        <div class="mt-2">
            @if ($soldOut)
                <span class="text-sm text-gray-500 dark:text-gray-400">Sold out</span>
            @elseif ($singleVariant)
                <button type="button"
                        x-data
                        @click="$dispatch('add-to-cart', { variantId: {{ $defaultVariant->id }}, quantity: 1 })"
                        class="w-full rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:opacity-0 sm:group-hover:opacity-100 dark:bg-blue-500 dark:hover:bg-blue-400 dark:focus:ring-offset-gray-950">
                    Add to cart
                </button>
            @else
                <a href="{{ $productUrl }}"
                   class="block text-sm font-medium text-blue-600 underline-offset-2 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                    Choose options
                </a>
            @endif
        </div>
    @endif
</div>
