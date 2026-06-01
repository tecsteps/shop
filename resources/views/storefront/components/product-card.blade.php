@props([
    // Product model (catalog) OR a normalized array. Accessed defensively so the
    // card renders before the catalog model API is finalised. Expected shape:
    // title, handle, price_amount, compare_at_amount, currency, image_url,
    // secondary_image_url, sold_out.
    'product',
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    $get = function (string $key, $default = null) use ($product) {
        if (is_array($product)) {
            return $product[$key] ?? $default;
        }
        // Eloquent models expose attributes/accessors via property access.
        return data_get($product, $key, $default);
    };

    $title = $get('title', 'Product');
    $handle = $get('handle');
    $currency = $get('currency', app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $price = (int) ($get('price_amount', $get('price', 0)));
    $compareAt = $get('compare_at_amount');
    $image = $get('image_url');
    $secondaryImage = $get('secondary_image_url');
    $soldOut = (bool) $get('sold_out', false);
    $onSale = $compareAt !== null && (int) $compareAt > $price;
    $url = $handle ? '/products/'.$handle : '#';
    $heading = in_array($headingLevel, ['h2', 'h3', 'h4'], true) ? $headingLevel : 'h3';
@endphp

<div {{ $attributes->merge(['class' => 'group relative flex flex-col']) }}>
    <a href="{{ $url }}" wire:navigate class="block focus:outline-none">
        <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($image)
                <img src="{{ $image }}" alt="{{ $title }}" loading="lazy"
                     class="h-full w-full object-cover transition duration-300 @if ($secondaryImage) group-hover:opacity-0 @else group-hover:scale-105 @endif" />
                @if ($secondaryImage)
                    <img src="{{ $secondaryImage }}" alt="" aria-hidden="true" loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-300 group-hover:opacity-100" />
                @endif
            @else
                <div class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-600" aria-hidden="true">
                    <flux:icon.shopping-bag class="size-12" />
                </div>
            @endif

            <div class="absolute left-2 top-2 flex flex-col gap-1">
                @if ($onSale)
                    <x-storefront::badge text="Sale" variant="sale" />
                    <span class="sr-only">On sale</span>
                @endif
                @if ($soldOut)
                    <x-storefront::badge text="Sold out" variant="sold-out" />
                @endif
            </div>
        </div>

        <div class="mt-3 space-y-1">
            <{{ $heading }} class="line-clamp-2 text-sm font-semibold text-zinc-900 dark:text-white">
                {{ $title }}
            </{{ $heading }}>
            <x-storefront::price :amount="$price" :currency="$currency" :compare-at-amount="$compareAt" class="text-sm" />
        </div>
    </a>

    @if ($showQuickAdd && ! $soldOut)
        <div class="mt-2 lg:opacity-0 lg:transition lg:group-hover:opacity-100">
            {{-- Quick-add wiring is provided by the shopping UI phase (task #6). --}}
            <a href="{{ $url }}" wire:navigate
               class="inline-flex w-full items-center justify-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-900 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white dark:hover:text-white">
                View product
            </a>
        </div>
    @endif
</div>
