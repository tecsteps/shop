@props([
    'product',
])

@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
    $defaultVariant = $product->variants->first();
    $firstImage = $product->media->sortBy('position')->first();
    $secondImage = $product->media->sortBy('position')->skip(1)->first();

    $hasSale = $defaultVariant
        && $defaultVariant->compare_at_price_amount
        && $defaultVariant->compare_at_price_amount > $defaultVariant->price_amount;

    $isSoldOut = $product->variants->every(function ($variant) {
        $inventory = $variant->inventoryItem;
        return $inventory
            && $inventory->policy === \App\Enums\InventoryPolicy::Deny
            && $inventory->quantityAvailable() <= 0;
    });
@endphp

<div {{ $attributes->merge(['class' => 'group']) }}>
    <a href="{{ route('storefront.products.show', $product->handle) }}" class="block" wire:navigate>
        <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($firstImage)
                <img
                    src="{{ $firstImage->url }}"
                    alt="{{ $firstImage->alt_text ?: $product->title }}"
                    class="size-full object-cover transition-opacity duration-300 @if ($secondImage) group-hover:opacity-0 @endif"
                    loading="lazy"
                />
                @if ($secondImage)
                    <img
                        src="{{ $secondImage->url }}"
                        alt="{{ $secondImage->alt_text ?: $product->title }}"
                        class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                        loading="lazy"
                    />
                @endif
            @else
                <div class="flex items-center justify-center size-full">
                    <flux:icon name="shopping-bag" class="size-12 text-zinc-300 dark:text-zinc-600" />
                </div>
            @endif

            <div class="absolute top-2 left-2 flex flex-col gap-1">
                @if ($hasSale)
                    <x-storefront.badge variant="sale">
                        <span class="sr-only">On </span>Sale
                    </x-storefront.badge>
                @endif
                @if ($isSoldOut)
                    <x-storefront.badge variant="sold-out">Sold out</x-storefront.badge>
                @endif
            </div>
        </div>

        <div class="mt-3 space-y-1">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white line-clamp-2">
                {{ $product->title }}
            </h3>
            @if ($product->vendor)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $product->vendor }}</p>
            @endif
            @if ($defaultVariant)
                <x-storefront.price
                    :amount="$defaultVariant->price_amount"
                    :currency="$currency"
                    :compare-at="$defaultVariant->compare_at_price_amount"
                    class="text-sm"
                />
            @endif
        </div>
    </a>
</div>
