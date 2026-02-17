@props([
    'product' => null,
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $primaryImage = $product->media->sortBy('position')->first();
    $secondaryImage = $product->media->sortBy('position')->skip(1)->first();
    $hasComparePrice = $defaultVariant && $defaultVariant->compare_at_amount && $defaultVariant->compare_at_amount > $defaultVariant->price_amount;
    $currency = $defaultVariant?->currency ?? 'EUR';

    $inventoryItem = $defaultVariant?->inventoryItem;
    $isSoldOut = $inventoryItem && $inventoryItem->policy === 'deny' && ($inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved) <= 0;
@endphp

<div class="group relative">
    <a href="/products/{{ $product->handle }}" class="block" aria-label="{{ $product->title }}">
        {{-- Image container --}}
        <div class="relative aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
            @if($primaryImage)
                <img src="{{ Storage::url($primaryImage->storage_key) }}"
                     alt="{{ $primaryImage->alt_text ?? $product->title }}"
                     class="h-full w-full object-cover transition-opacity duration-300 @if($secondaryImage) group-hover:opacity-0 @endif"
                     loading="lazy">
                @if($secondaryImage)
                    <img src="{{ Storage::url($secondaryImage->storage_key) }}"
                         alt="{{ $secondaryImage->alt_text ?? $product->title }}"
                         class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                         loading="lazy">
                @endif
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            @endif

            {{-- Badges --}}
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                @if($hasComparePrice && !$isSoldOut)
                    <x-storefront.components.badge text="Sale" variant="sale" />
                @endif
                @if($isSoldOut)
                    <x-storefront.components.badge text="Sold out" variant="sold-out" />
                @endif
            </div>
        </div>

        {{-- Text content --}}
        <div class="mt-3">
            <{{ $headingLevel }} class="text-sm font-semibold text-gray-900 line-clamp-2 dark:text-white">
                {{ $product->title }}
            </{{ $headingLevel }}>
            @if($defaultVariant)
                <div class="mt-1">
                    <x-storefront.components.price
                        :amount="$defaultVariant->price_amount"
                        :currency="$currency"
                        :compare-at-amount="$defaultVariant->compare_at_amount"
                        class="text-sm font-semibold"
                    />
                </div>
            @endif
        </div>
    </a>

    {{-- Quick add --}}
    @if($showQuickAdd && !$isSoldOut)
        <div class="mt-2 opacity-100 transition-opacity lg:opacity-0 lg:group-hover:opacity-100">
            @if($product->variants->count() > 1)
                <a href="/products/{{ $product->handle }}"
                   class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Choose options
                </a>
            @else
                <button class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        type="button">
                    Add to cart
                </button>
            @endif
        </div>
    @endif
</div>
