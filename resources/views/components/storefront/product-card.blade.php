@props(['product', 'headingLevel' => 'h3', 'showQuickAdd' => true])

@php
    $firstVariant = $product->variants->first();
    $price = $firstVariant?->price_amount ?? 0;
    $compareAtPrice = $firstVariant?->compare_at_amount;
    $currency = $product->store?->currency ?? 'USD';
    $image = $product->media->first();
    $isSale = $compareAtPrice && $compareAtPrice > $price;
    $handle = $product->handle ?? $product->id;
@endphp

<div class="group relative">
    <a href="{{ url('/products/' . $handle) }}" class="block">
        {{-- Image --}}
        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
            @if($image)
                <img
                    src="{{ $image->url ?? '' }}"
                    alt="{{ $product->title }}"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                >
            @else
                <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-600">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                </div>
            @endif

            @if($isSale)
                <span class="absolute top-2 left-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">Sale</span>
            @endif
        </div>

        {{-- Text --}}
        <div class="mt-3">
            <{{ $headingLevel }} class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $product->title }}</{{ $headingLevel }}>
            <div class="mt-1">
                <x-storefront.price :amount="$price" :currency="$currency" :compare-at-amount="$isSale ? $compareAtPrice : null" />
            </div>
        </div>
    </a>
</div>
