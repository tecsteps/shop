@props(['product', 'headingLevel' => 'h3', 'showQuickAdd' => true])

@php
    $firstVariant = $product->variants->first();
    $price = $firstVariant?->price_amount ?? 0;
    $compareAt = $firstVariant?->compare_at_amount;
    $currency = $firstVariant?->currency ?? 'EUR';
    $firstMedia = $product->media->sortBy('position')->first();
    $isOnSale = $compareAt && $compareAt > $price;
    $hasMultipleVariants = $product->variants->count() > 1;

    $allSoldOut = $product->variants->every(function ($variant) {
        $inv = $variant->inventoryItem;
        return $inv && $inv->policy === \App\Enums\InventoryPolicy::Deny && $inv->available <= 0;
    });
@endphp

<div class="group">
    <a href="{{ route('storefront.products.show', $product->handle) }}" class="block">
        {{-- Image --}}
        <div class="relative aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden mb-3">
            @if($firstMedia)
                <img src="{{ $firstMedia->storage_key }}"
                     alt="{{ $firstMedia->alt_text ?? $product->title }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     loading="lazy">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-600">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            @endif

            {{-- Badges --}}
            <div class="absolute top-2 left-2 flex flex-col gap-1">
                @if($isOnSale && !$allSoldOut)
                    <span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-xs font-medium px-2 py-0.5 rounded-full">
                        <span class="sr-only">On sale</span>Sale
                    </span>
                @endif
                @if($allSoldOut)
                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium px-2 py-0.5 rounded-full">
                        Sold out
                    </span>
                @endif
            </div>
        </div>

        {{-- Text --}}
        <{{ $headingLevel }} class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 mb-1">
            {{ $product->title }}
        </{{ $headingLevel }}>

        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ \App\Support\MoneyFormatter::format($price, $currency) }}
            </span>
            @if($isOnSale)
                <span class="text-sm text-gray-500 line-through">
                    {{ \App\Support\MoneyFormatter::format($compareAt, $currency) }}
                </span>
            @endif
        </div>
    </a>

    @if($showQuickAdd)
        <div class="mt-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
            @if($allSoldOut)
                <span class="text-sm text-gray-400">Sold out</span>
            @elseif($hasMultipleVariants)
                <a href="{{ route('storefront.products.show', $product->handle) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Choose options</a>
            @else
                <span class="text-sm text-blue-600 dark:text-blue-400">Add to cart</span>
            @endif
        </div>
    @endif
</div>
