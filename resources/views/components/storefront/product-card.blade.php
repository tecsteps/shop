@props(['product'])

@php
    $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $primaryImage = $product->media->sortBy('position')->first();
    $secondaryImage = $product->media->sortBy('position')->skip(1)->first();
    $isOnSale = $defaultVariant && $defaultVariant->compare_at_amount && $defaultVariant->compare_at_amount > $defaultVariant->price_amount;
    $isSoldOut = $defaultVariant && $defaultVariant->inventoryItem
        && $defaultVariant->inventoryItem->quantity_available <= 0
        && $defaultVariant->inventoryItem->policy === \App\Enums\InventoryPolicy::Deny;
@endphp

<a href="/products/{{ $product->handle }}" class="group block" {{ $attributes }}>
    {{-- Image --}}
    <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
        @if($primaryImage)
            <img src="{{ $primaryImage->storage_key }}"
                 alt="{{ $primaryImage->alt_text ?? $product->title }}"
                 class="h-full w-full object-cover transition-opacity duration-300 {{ $secondaryImage ? 'group-hover:opacity-0' : '' }}"
                 loading="lazy">
            @if($secondaryImage)
                <img src="{{ $secondaryImage->storage_key }}"
                     alt=""
                     class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                     loading="lazy">
            @endif
        @else
            <div class="flex h-full w-full items-center justify-center">
                <svg class="h-10 w-10 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
        @endif

        {{-- Badges --}}
        <div class="absolute left-2 top-2 flex flex-col gap-1">
            @if($isOnSale)
                <x-storefront.badge type="sale">Sale</x-storefront.badge>
            @endif
            @if($isSoldOut)
                <x-storefront.badge type="sold-out">Sold out</x-storefront.badge>
            @endif
        </div>
    </div>

    {{-- Text --}}
    <div class="mt-3">
        <h3 class="text-sm font-semibold text-zinc-900 line-clamp-2 dark:text-white">{{ $product->title }}</h3>
        @if($defaultVariant)
            <div class="mt-1">
                <x-storefront.price :amount="$defaultVariant->price_amount"
                                    :compare-at="$defaultVariant->compare_at_amount"
                                    :currency="$defaultVariant->currency" />
            </div>
        @endif
    </div>
</a>
