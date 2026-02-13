@props([
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $primaryImage = $product->media->sortBy('position')->first();
    $secondaryImage = $product->media->sortBy('position')->skip(1)->first();
    $price = $defaultVariant?->price_amount ?? 0;
    $compareAt = $defaultVariant?->compare_at_amount;
    $currency = $defaultVariant?->currency ?? 'USD';
    $isOnSale = $compareAt && $compareAt > $price;
    $isSoldOut = $product->variants->every(function ($variant) {
        $inventory = $variant->inventoryItem ?? null;
        if (!$inventory) {
            return false;
        }
        return $inventory->policy === 'deny' && ($inventory->quantity_on_hand - $inventory->quantity_reserved) <= 0;
    });
    $hasMultipleVariants = $product->variants->count() > 1;
@endphp

<div {{ $attributes->class(['group relative']) }}>
    <a href="{{ route('storefront.products.show', $product->handle) }}" class="block">
        {{-- Image --}}
        <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if($primaryImage)
                <img src="{{ $primaryImage->storage_key }}"
                     alt="{{ $primaryImage->alt_text ?? $product->title }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition-opacity duration-300 @if($secondaryImage) group-hover:opacity-0 @endif">
                @if($secondaryImage)
                    <img src="{{ $secondaryImage->storage_key }}"
                         alt="{{ $secondaryImage->alt_text ?? $product->title }}"
                         loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                @endif
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <svg class="h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            @endif

            {{-- Badges --}}
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                @if($isOnSale)
                    <x-storefront.badge text="Sale" variant="sale" />
                @endif
                @if($isSoldOut)
                    <x-storefront.badge text="Sold out" variant="sold-out" />
                @endif
            </div>
        </div>

        {{-- Text Content --}}
        <div class="mt-3">
            <{{ $headingLevel }} class="text-sm font-semibold text-zinc-900 dark:text-white line-clamp-2">
                {{ $product->title }}
            </{{ $headingLevel }}>
            <div class="mt-1">
                <x-storefront.price :amount="$price" :currency="$currency" :compare-at-amount="$compareAt" class="text-sm" />
            </div>
        </div>
    </a>

    {{-- Quick Add --}}
    @if($showQuickAdd && !$isSoldOut)
        <div class="mt-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
            @if($hasMultipleVariants)
                <a href="{{ route('storefront.products.show', $product->handle) }}"
                   class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Choose options
                </a>
            @else
                <button type="button"
                        class="w-full rounded-md bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100 transition-colors">
                    Add to cart
                </button>
            @endif
        </div>
    @endif
</div>
