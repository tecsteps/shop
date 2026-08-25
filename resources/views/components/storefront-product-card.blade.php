@props([
    'product' => null,
    'headingLevel' => 'h3',
    'showQuickAdd' => true,
])

@php
    if (! $product) {
        return;
    }

    $activeVariants = $product->variants->where('status', 'active');
    $defaultVariant = $activeVariants->firstWhere('is_default', true) ?? $activeVariants->first();
    $price = $defaultVariant?->price_amount ?? $activeVariants->min('price_amount') ?? 0;
    $compareAt = $defaultVariant?->compare_at_amount ?? null;
    $hasSale = $compareAt !== null && (int) $compareAt > (int) $price;
    $currency = $defaultVariant?->currency ?? ($currentStore?->default_currency ?? 'EUR');

    $soldOut = $activeVariants->isEmpty()
        || $activeVariants->every(function ($variant) {
            $inventory = $variant->inventoryItem;

            return $inventory
                && $inventory->policy === 'deny'
                && ($inventory->quantity_on_hand - $inventory->quantity_reserved) <= 0;
        });

    $images = $product->media->where('type', 'image')->values();
    $primary = $images->first();
    $secondary = $images->get(1);

    $url = route('storefront.product', ['handle' => $product->handle]);

    $heading = $headingLevel;
@endphp

<div class="group relative flex flex-col">
    <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
        <a href="{{ $url }}" class="block h-full w-full" aria-label="{{ $product->title }}">
            @if ($primary)
                <img
                    src="{{ Storage::url($primary->storage_key) }}"
                    alt="{{ $primary->alt_text ?: $product->title }}"
                    loading="lazy"
                    class="h-full w-full object-cover transition-opacity duration-300 {{ $secondary ? 'group-hover:opacity-0' : '' }}"
                />
            @else
                <span class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-600">
                    <svg class="size-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                        <path d="M3 6h18" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </span>
            @endif

            @if ($secondary)
                <img
                    src="{{ Storage::url($secondary->storage_key) }}"
                    alt=""
                    aria-hidden="true"
                    loading="lazy"
                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                />
            @endif
        </a>

        @if ($hasSale || $soldOut)
            <div class="absolute left-2.5 top-2.5 flex flex-col items-start gap-1.5">
                @if ($hasSale)
                    <x-storefront-badge variant="sale" text="Sale" />
                @endif
                @if ($soldOut)
                    <x-storefront-badge variant="sold-out" text="Sold out" />
                @endif
            </div>
        @endif
    </div>

    <div class="mt-3 flex flex-1 flex-col gap-1.5">
        <{{ $heading }} class="text-sm font-semibold leading-snug text-zinc-900 dark:text-white">
            <a href="{{ $url }}" class="line-clamp-2 transition hover:opacity-75">{{ $product->title }}</a>
        </{{ $heading }}>

        <x-storefront-price :amount="$price" :currency="$currency" :compare-at-amount="$compareAt" class="text-sm" />

        @if ($showQuickAdd)
            @if ($soldOut)
                <span class="pointer-events-none mt-1 inline-flex w-full items-center justify-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">
                    Sold out
                </span>
            @elseif ($activeVariants->count() === 1)
                <button
                    type="button"
                    wire:click="quickAdd({{ $activeVariants->first()->id }})"
                    class="mt-1 inline-flex w-full items-center justify-center rounded-lg border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 sm:translate-y-1 sm:opacity-0 sm:transition sm:group-hover:translate-y-0 sm:group-hover:opacity-100 dark:border-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Add to cart
                </button>
            @else
                <a
                    href="{{ $url }}"
                    class="mt-1 inline-flex w-full items-center justify-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-900 hover:text-zinc-900 sm:translate-y-1 sm:opacity-0 sm:transition sm:group-hover:translate-y-0 sm:group-hover:opacity-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white dark:hover:text-white"
                >
                    Choose options
                </a>
            @endif
        @endif
    </div>
</div>
