@props(['product'])

@php
    $variant = $product->variants->first();
    $isSoldOut = $product->variants->isNotEmpty()
        && $product->variants->every(fn ($variant) => $variant->inventoryItem?->policy?->value === 'deny' && $variant->inventoryItem?->availableQuantity() <= 0);
    $isOnSale = $variant?->compare_at_amount && $variant->compare_at_amount > $variant->price_amount;
@endphp

<article {{ $attributes->class('group') }}>
    <a href="{{ route('products.show', $product->handle) }}" class="block space-y-3" wire:navigate>
        <div class="relative aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute left-3 top-3 z-10 flex flex-wrap gap-2">
                @if ($isOnSale)
                    <span class="rounded-full bg-rose-600 px-2 py-1 text-xs font-medium text-white">Sale</span>
                @endif

                @if ($isSoldOut)
                    <span class="rounded-full bg-zinc-950 px-2 py-1 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-950">Sold out</span>
                @endif
            </div>

            <div class="flex h-full items-center justify-center text-zinc-400 transition-transform duration-200 group-hover:scale-105 dark:text-zinc-600">
                <flux:icon name="shopping-bag" class="size-12" />
            </div>
        </div>

        <div class="space-y-1">
            <h3 class="line-clamp-2 text-sm font-medium text-zinc-950 dark:text-white">{{ $product->title }}</h3>

            @if ($variant)
                <x-storefront.price :amount="$variant->price_amount" :compare-at="$variant->compare_at_amount" :currency="$variant->currency" class="text-sm" />
            @endif

            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $product->variants_count ?? $product->variants->count() }} {{ Str::plural('variant', $product->variants_count ?? $product->variants->count()) }}
            </p>
        </div>
    </a>
</article>
