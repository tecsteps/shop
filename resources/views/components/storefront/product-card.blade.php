@props([
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    $media = $product->media->sortBy('position');
    $primaryImage = $media->first();
    $secondaryImage = $media->skip(1)->first();
    $variants = $product->variants;
    $defaultVariant = $variants->firstWhere('is_default', true) ?? $variants->first();
    $singleVariant = $variants->count() <= 1;
    $soldOut = $variants->isNotEmpty() && $variants->every(function ($variant) {
        $inventory = $variant->inventoryItem;

        return $inventory && $inventory->policy->value === 'deny' && $inventory->availableQuantity() <= 0;
    });
    $productUrl = route('storefront.products.show', $product->handle);
@endphp

<div {{ $attributes->class(['group relative']) }}>
    <a href="{{ $productUrl }}" wire:navigate class="block">
        <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
            @if ($primaryImage)
                <img
                    src="{{ $primaryImage->url ?? Storage::url($primaryImage->storage_key) }}"
                    alt="{{ $primaryImage->alt_text ?? $product->title }}"
                    loading="lazy"
                    class="absolute inset-0 size-full object-cover transition-opacity duration-300 {{ $secondaryImage ? 'group-hover:opacity-0' : '' }}"
                />

                @if ($secondaryImage)
                    <img
                        src="{{ $secondaryImage->url ?? Storage::url($secondaryImage->storage_key) }}"
                        alt=""
                        loading="lazy"
                        class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    />
                @endif
            @else
                <div class="flex size-full items-center justify-center text-zinc-400 dark:text-zinc-600">
                    <flux:icon name="shopping-bag" class="size-10" />
                </div>
            @endif

            <div class="absolute top-2 left-2 flex flex-col gap-1">
                @if ($defaultVariant && $defaultVariant->compare_at_amount > $defaultVariant->price_amount)
                    <x-storefront.badge text="Sale" variant="sale" />
                @endif

                @if ($soldOut)
                    <x-storefront.badge text="Sold out" variant="sold-out" />
                @endif
            </div>
        </div>

        <{{ $headingLevel }} class="mt-3 line-clamp-2 text-sm font-semibold text-zinc-900 dark:text-white">
            {{ $product->title }}
        </{{ $headingLevel }}>
    </a>

    @if ($defaultVariant)
        <div class="mt-1">
            <x-storefront.price
                :amount="$defaultVariant->price_amount"
                :currency="$defaultVariant->currency"
                :compare-at-amount="$defaultVariant->compare_at_amount"
            />
        </div>
    @endif

    @if ($showQuickAdd)
        <a
            href="{{ $productUrl }}"
            wire:navigate
            class="mt-2 block text-sm font-medium text-blue-600 opacity-100 hover:text-blue-700 lg:opacity-0 lg:group-hover:opacity-100 dark:text-blue-400 dark:hover:text-blue-300"
        >
            {{ $singleVariant ? 'Add to cart' : 'Choose options' }}
        </a>
    @endif
</div>
