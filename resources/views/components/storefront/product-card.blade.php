@props([
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
    'quickAddMethod' => 'addToCart',
])

@php
    $headingLevel = in_array(strtolower((string) $headingLevel), ['h2', 'h3', 'h4', 'h5', 'h6'], true)
        ? strtolower((string) $headingLevel)
        : 'h3';

    $title = (string) data_get($product, 'title', __('Untitled product'));
    $handle = (string) data_get($product, 'handle', '');
    $productUrl = url('/products/'.rawurlencode($handle));
    $enumValue = static fn (mixed $value): mixed => $value instanceof \BackedEnum ? $value->value : $value;

    $variants = collect(data_get($product, 'variants', []))
        ->filter(fn (mixed $variant): bool => $enumValue(data_get($variant, 'status', 'active')) !== 'archived')
        ->sortBy(fn (mixed $variant): int => (int) data_get($variant, 'position', 0))
        ->values();

    $defaultVariant = $variants->first(fn (mixed $variant): bool => (bool) data_get($variant, 'is_default'))
        ?? $variants->first();

    $amount = (int) data_get($defaultVariant, 'price_amount', 0);
    $compareAtAmount = data_get($defaultVariant, 'compare_at_amount');
    $currency = (string) data_get($defaultVariant, 'currency', 'USD');
    $isOnSale = filled($compareAtAmount) && (int) $compareAtAmount > $amount;

    $isVariantUnavailable = static function (mixed $variant) use ($enumValue): bool {
        $inventory = data_get($variant, 'inventoryItem', data_get($variant, 'inventory_item'));

        if ($inventory === null) {
            return false;
        }

        if ($enumValue(data_get($inventory, 'policy', 'deny')) === 'continue') {
            return false;
        }

        $available = data_get($inventory, 'available_quantity');
        $available ??= (int) data_get($inventory, 'quantity_on_hand', 0)
            - (int) data_get($inventory, 'quantity_reserved', 0);

        return (int) $available <= 0;
    };

    $isSoldOut = $variants->isNotEmpty() && $variants->every($isVariantUnavailable);

    $media = collect(data_get($product, 'media', []))
        ->filter(fn (mixed $item): bool => $enumValue(data_get($item, 'type', 'image')) === 'image'
            && $enumValue(data_get($item, 'status', 'ready')) === 'ready')
        ->sortBy(fn (mixed $item): int => (int) data_get($item, 'position', 0))
        ->values();

    $mediaUrl = static function (mixed $item): ?string {
        $url = data_get($item, 'url');

        if (filled($url)) {
            return (string) $url;
        }

        $storageKey = data_get($item, 'storage_key');

        if (blank($storageKey)) {
            return null;
        }

        if (str_starts_with((string) $storageKey, 'http://') || str_starts_with((string) $storageKey, 'https://') || str_starts_with((string) $storageKey, '/')) {
            return (string) $storageKey;
        }

        return asset('storage/'.ltrim((string) $storageKey, '/'));
    };

    $primaryMedia = $media->get(0);
    $secondaryMedia = $media->get(1);
    $primaryImageUrl = $mediaUrl($primaryMedia);
    $secondaryImageUrl = $mediaUrl($secondaryMedia);
    $primaryAlt = (string) data_get($primaryMedia, 'alt_text', $title);
    $primaryWidth = (int) data_get($primaryMedia, 'width', 600);
    $primaryHeight = (int) data_get($primaryMedia, 'height', 600);
    $primaryWidth = $primaryWidth > 0 ? $primaryWidth : 600;
    $primaryHeight = $primaryHeight > 0 ? $primaryHeight : 600;

    $quickAddMethod = preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', (string) $quickAddMethod) === 1
        ? (string) $quickAddMethod
        : 'addToCart';
    $variantId = (int) data_get($defaultVariant, 'id', 0);
    $quickAddAction = $quickAddMethod.'('.$variantId.')';
@endphp

<article {{ $attributes->class('storefront-product-card group relative flex h-full min-w-0 flex-col') }}>
    <a href="{{ $productUrl }}" wire:navigate class="relative block overflow-hidden rounded-xl bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--storefront-primary)] dark:bg-zinc-800" aria-label="{{ $title }}">
        <span class="block aspect-square">
            @if ($primaryImageUrl)
                <img
                    src="{{ $primaryImageUrl }}"
                    alt="{{ $primaryAlt }}"
                    class="size-full object-cover transition duration-500 motion-reduce:transition-none"
                    loading="lazy"
                    decoding="async"
                    width="{{ $primaryWidth }}"
                    height="{{ $primaryHeight }}"
                >

                @if ($secondaryImageUrl)
                    <img
                        src="{{ $secondaryImageUrl }}"
                        alt=""
                        class="storefront-product-card__secondary absolute inset-0 size-full object-cover"
                        loading="lazy"
                        decoding="async"
                        aria-hidden="true"
                    >
                @endif
            @else
                <span class="flex size-full items-center justify-center text-zinc-400 dark:text-zinc-600" aria-hidden="true">
                    <svg class="size-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                        <path d="M6.75 8.25h10.5l.75 12H6l.75-12Z" stroke-linejoin="round" />
                        <path d="M9 9V6a3 3 0 0 1 6 0v3" stroke-linecap="round" />
                    </svg>
                </span>
            @endif
        </span>

        @if ($isOnSale || $isSoldOut)
            <span class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
                @if ($isOnSale)
                    <x-storefront.badge :text="__('Sale')" variant="sale" />
                @endif
                @if ($isSoldOut)
                    <x-storefront.badge :text="__('Sold out')" variant="sold-out" />
                @endif
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col pt-4">
        <{{ $headingLevel }} class="storefront-line-clamp-2 text-sm font-semibold leading-5 text-zinc-950 dark:text-white">
            <a href="{{ $productUrl }}" wire:navigate class="rounded-sm underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--storefront-primary)]">
                {{ $title }}
            </a>
        </{{ $headingLevel }}>

        <div class="mt-1.5" aria-live="polite">
            <x-storefront.price :$amount :$currency :$compareAtAmount :show-sale-badge="false" />
        </div>

        @if ($showQuickAdd)
            <div class="storefront-product-card__quick-add mt-auto pt-3">
                @if ($isSoldOut || $variants->isEmpty())
                    <button type="button" class="storefront-button-secondary w-full" disabled>
                        {{ __('Sold out') }}
                    </button>
                @elseif ($variants->count() === 1)
                    <button
                        type="button"
                        class="storefront-button-secondary w-full"
                        wire:click="{{ $quickAddAction }}"
                        wire:loading.attr="disabled"
                        wire:target="{{ $quickAddAction }}"
                        aria-label="{{ __('Add :product to cart', ['product' => $title]) }}"
                    >
                        <span wire:loading.remove wire:target="{{ $quickAddAction }}">{{ __('Add to cart') }}</span>
                        <span wire:loading wire:target="{{ $quickAddAction }}">{{ __('Adding…') }}</span>
                    </button>
                @else
                    <a href="{{ $productUrl }}" wire:navigate class="storefront-button-secondary w-full">
                        {{ __('Choose options') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</article>
