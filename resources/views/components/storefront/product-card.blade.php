@props(['product'])
@php($variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first())
<article {{ $attributes->class(['group overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <a href="{{ route('storefront.products.show', $product->handle) }}" wire:navigate class="block">
        <div class="flex aspect-square items-center justify-center bg-zinc-100 text-zinc-400 dark:bg-zinc-800" role="img" aria-label="{{ $product->title }} image placeholder">
            <span class="text-sm">{{ $product->media->first()?->alt_text ?? $product->title }}</span>
        </div>
        <div class="grid gap-2 p-4">
            <h2 class="font-medium group-hover:underline">{{ $product->title }}</h2>
            @if ($variant)
                <x-storefront.price :amount="$variant->price_amount" :compare-at-amount="$variant->compare_at_amount" :currency="$variant->currency" />
            @endif
        </div>
    </a>
</article>
