@php
    $variant = $product->variants->first();
    $image = $product->media->first();
    $hasMultipleVariants = $product->variants->count() > 1;
    $priceAmount = $variant?->price_amount ?? 0;
    $compareAtAmount = $variant?->compare_at_amount;
    $isOnSale = $compareAtAmount && $compareAtAmount > $priceAmount;
    $currency = $currentStore->default_currency ?? 'EUR';
@endphp

<a href="/products/{{ $product->handle }}" class="group">
    <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
        @if($image)
            <img src="{{ $image->url ?? '' }}"
                 alt="{{ $product->title }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center">
                <svg class="h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
            </div>
        @endif

        {{-- Badges --}}
        @if($isOnSale)
            @include('storefront.components.badge', ['text' => 'Sale', 'variant' => 'sale'])
        @endif
    </div>

    <div class="mt-3">
        <h3 class="text-sm font-medium text-zinc-900 group-hover:underline dark:text-white">{{ $product->title }}</h3>

        <div class="mt-1">
            @include('storefront.components.price', [
                'amount' => $priceAmount,
                'currency' => $currency,
                'compareAtAmount' => $isOnSale ? $compareAtAmount : null,
                'compact' => true,
            ])
        </div>

        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            {{ $hasMultipleVariants ? 'Choose options' : 'Add to cart' }}
        </p>
    </div>
</a>
