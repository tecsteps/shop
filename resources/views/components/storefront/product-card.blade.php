@props(['product', 'headingLevel' => 'h3', 'showQuickAdd' => true])

@php
    $defaultVariant = $product->variants->first();
    $primaryImage = $product->media->sortBy('position')->first();
    $price = $defaultVariant?->price ?? 0;
    $compareAtPrice = $defaultVariant?->compare_at_price;
    $currency = app()->bound('current_store') ? app('current_store')->default_currency : 'EUR';
    $inStock = $defaultVariant?->inventoryItem?->quantity > 0;
@endphp

<article {{ $attributes->class(['group relative']) }}>
    <a href="{{ route('storefront.products.show', $product->handle) }}"
       class="block"
       aria-label="{{ $product->title }}">
        {{-- Image --}}
        <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
            @if($primaryImage)
                <img src="{{ $primaryImage->url }}"
                     alt="{{ $primaryImage->alt ?? $product->title }}"
                     class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                     loading="lazy">
            @else
                <div class="flex h-full items-center justify-center">
                    <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            @endif

            {{-- Badges --}}
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                @if(!$inStock)
                    <x-storefront.badge text="Sold out" variant="sold-out" />
                @elseif($compareAtPrice && $compareAtPrice > $price)
                    <x-storefront.badge text="Sale" variant="sale" />
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="mt-3">
            <{{ $headingLevel }} class="text-sm font-medium text-gray-900 dark:text-white group-hover:underline">
                {{ $product->title }}
            </{{ $headingLevel }}>
            <div class="mt-1">
                <x-storefront.price :amount="$price" :currency="$currency" :compare-at-amount="$compareAtPrice" />
            </div>
        </div>
    </a>
</article>
