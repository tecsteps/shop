@props([
    'product' => null,
    'currency' => 'EUR',
])

@php
    $title = $product->title ?? 'Product';
    $handle = $product->handle ?? '#';
    $price = $product->price_amount ?? 0;
    $compareAtPrice = $product->compare_at_price_amount ?? null;
    $isOnSale = $compareAtPrice && $compareAtPrice > $price;
    $image = $product->media?->first();
    $imageUrl = $image?->url ?? null;
    $imageAlt = $image?->alt_text ?? $title;
@endphp

<article class="group">
    <a href="/products/{{ $handle }}" class="block" aria-label="{{ $title }}">
        {{-- Image --}}
        <div class="relative aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
            @if($imageUrl)
                <img src="{{ $imageUrl }}"
                     alt="{{ $imageAlt }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-600">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            @endif

            {{-- Badges --}}
            @if($isOnSale)
                <div class="absolute left-2 top-2">
                    <x-storefront.badge variant="sale">
                        <span class="sr-only">On sale</span>
                        Sale
                    </x-storefront.badge>
                </div>
            @endif
        </div>

        {{-- Text Content --}}
        <div class="mt-3">
            <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
                {{ $title }}
            </h3>
            <div class="mt-1 flex items-center gap-2">
                <x-storefront.price :amount="$price" :currency="$currency" class="text-sm font-semibold text-gray-900 dark:text-white" />
                @if($isOnSale)
                    <x-storefront.price :amount="$compareAtPrice" :currency="$currency" class="text-sm text-gray-500 line-through dark:text-gray-400" />
                @endif
            </div>
        </div>
    </a>
</article>
