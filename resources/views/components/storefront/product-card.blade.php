@props(['product', 'headingLevel' => 'h3', 'showQuickAdd' => true])

@php
    $catalog = app(\App\Services\StorefrontCatalog::class);
    $variant = $catalog->displayVariant($product);
    $inStock = $catalog->isInStock($product);
    $image = $catalog->primaryImageUrl($product);
    $currency = $variant?->currency ?? $product->store?->default_currency ?? 'USD';
    $url = route('storefront.products.show', ['handle' => $product->handle]);
@endphp

<div {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <a href="{{ $url }}" class="block" aria-label="{{ $product->title }}">
        <div class="relative aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($image)
                <img src="{{ $image }}" alt="{{ $catalog->primaryImageAlt($product) }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
            @else
                <div class="flex h-full w-full items-center justify-center text-zinc-400">
                    <flux:icon.shopping-bag class="size-12" variant="outline" />
                </div>
            @endif
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                @if ($variant && $variant->compare_at_amount && $variant->compare_at_amount > $variant->price_amount)
                    <x-storefront.badge text="Sale" variant="sale" />
                @endif
                @if (! $inStock)
                    <x-storefront.badge text="Sold out" variant="sold-out" />
                @endif
            </div>
        </div>
    </a>
    <div class="flex flex-1 flex-col gap-1 p-4">
        <{{ $headingLevel }} class="line-clamp-2 text-sm font-semibold">
            <a href="{{ $url }}">{{ $product->title }}</a>
        </{{ $headingLevel }}>
        @if ($variant)
            <x-storefront.price :amount="$variant->price_amount" :currency="$currency" :compareAtAmount="$variant->compare_at_amount" class="text-sm" />
        @endif
        @if ($showQuickAdd)
            @if ($product->productVariants->where('status', \App\Enums\VariantStatus::Active)->count() > 1)
                <a href="{{ $url }}" class="mt-1 text-sm text-blue-600 hover:underline dark:text-blue-400">Choose options</a>
            @elseif ($variant && $inStock)
                <livewire:storefront.quick-add :variantId="$variant->getKey()" :wire:key="'quick-add-'.$product->getKey()" />
            @endif
        @endif
    </div>
</div>
