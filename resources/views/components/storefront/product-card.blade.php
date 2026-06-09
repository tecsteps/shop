@props([
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    use App\Enums\InventoryPolicy;
    use App\Enums\MediaStatus;
    use Illuminate\Support\Facades\Storage;

    /** @var \App\Models\Product $product */
    $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $priceAmount = $variant?->price_amount ?? 0;
    $compareAtAmount = $variant?->compare_at_amount;
    $currency = $variant?->currency ?? ($currentStore->default_currency ?? 'EUR');

    $isOnSale = $compareAtAmount !== null && $compareAtAmount > $priceAmount;
    $isSoldOut = $product->variants->isNotEmpty() && $product->variants->every(
        fn ($productVariant): bool => $productVariant->inventoryItem !== null
            && $productVariant->inventoryItem->availableQuantity() <= 0
            && $productVariant->inventoryItem->policy === InventoryPolicy::Deny,
    );

    $primaryImage = $product->media->firstWhere('status', MediaStatus::Ready) ?? $product->media->first();
    $imageUrl = $primaryImage !== null ? Storage::disk('public')->url($primaryImage->storage_key) : null;

    $headingTag = in_array($headingLevel, ['h2', 'h3', 'h4'], true) ? $headingLevel : 'h3';
    $hasMultipleVariants = $product->variants->count() > 1;
@endphp

<article {{ $attributes->class('group relative flex flex-col') }}>
    <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
        @if ($imageUrl !== null)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $primaryImage->alt_text ?? $product->title }}"
                loading="lazy"
                class="size-full object-cover transition duration-300 group-hover:scale-105"
            />
        @else
            <div class="flex size-full items-center justify-center text-zinc-300 dark:text-zinc-600">
                <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                </svg>
            </div>
        @endif

        @if ($isOnSale || $isSoldOut)
            <div class="absolute top-2 left-2 flex flex-col items-start gap-1">
                @if ($isOnSale)
                    <x-storefront.badge :text="__('Sale')" variant="sale" aria-label="{{ __('On sale') }}" />
                @endif
                @if ($isSoldOut)
                    <x-storefront.badge :text="__('Sold out')" variant="sold-out" />
                @endif
            </div>
        @endif
    </div>

    <div class="mt-3 flex flex-1 flex-col gap-1">
        <{{ $headingTag }} class="text-sm font-semibold text-zinc-900 dark:text-white">
            <a
                href="{{ route('storefront.products.show', $product->handle) }}"
                class="line-clamp-2 after:absolute after:inset-0 after:rounded-xl focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-blue-600"
            >
                {{ $product->title }}
            </a>
        </{{ $headingTag }}>

        <x-storefront.price :amount="$priceAmount" :currency="$currency" :compare-at-amount="$compareAtAmount" class="text-sm" />

        @if ($showQuickAdd && ! $isSoldOut)
            <span class="mt-1 text-xs font-medium text-blue-700 opacity-100 transition lg:opacity-0 lg:group-hover:opacity-100 lg:group-focus-within:opacity-100 dark:text-blue-400">
                {{ $hasMultipleVariants ? __('Choose options') : __('View product') }}
            </span>
        @endif
    </div>
</article>
