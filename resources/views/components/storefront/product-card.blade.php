@props(['product'])
@php($variant = $product->defaultVariant())
<article class="group" wire:key="product-{{ $product->id }}">
    <a href="{{ route('product.show', $product->handle) }}" class="block" wire:navigate aria-label="{{ $product->title }}">
        <div class="relative aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
            @if ($product->media->first()?->url)
                <img src="{{ $product->media->first()->url }}" alt="{{ $product->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
            @else
                <div class="flex h-full items-center justify-center text-4xl text-zinc-400">⌂</div>
            @endif
            @if ($variant?->compare_at_amount > $variant?->price_amount)<span class="absolute left-3 top-3 rounded-full bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white">Sale</span>@endif
            @if ($product->variants->every(fn ($item): bool => $item->availableQuantity() <= 0) && $product->variants->every(fn ($item): bool => $item->inventory?->policy?->value !== 'continue'))<span class="absolute right-3 top-3 rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-semibold text-white">Sold out</span>@endif
        </div>
        <div class="mt-3">
            <h3 class="font-semibold">{{ $product->title }}</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">€{{ number_format(($variant?->price_amount ?? 0) / 100, 2) }} @if ($variant?->compare_at_amount)<span class="ml-1 line-through">€{{ number_format($variant->compare_at_amount / 100, 2) }}</span>@endif</p>
        </div>
    </a>
</article>
