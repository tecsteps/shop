@props(['product'])

@php($variant = $product->defaultVariant ?? $product->variants->first())

<article class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <a href="{{ route('products.show', $product->handle) }}" class="block">
        <img
            src="{{ $product->media->first()?->url ?? 'https://placehold.co/900x1100/e5e7eb/111827?text='.urlencode($product->title) }}"
            alt="{{ $product->media->first()?->alt_text ?? $product->title }}"
            class="aspect-[4/5] w-full object-cover"
            loading="lazy"
        >
    </a>
    <div class="grid gap-3 p-4">
        <div>
            <h3 class="font-semibold text-zinc-950 dark:text-white">
                <a href="{{ route('products.show', $product->handle) }}">{{ $product->title }}</a>
            </h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $product->product_type }}</p>
        </div>
        <div class="flex items-center justify-between gap-3">
            <div class="font-medium">
                @if($variant)
                    <x-shop.price :amount="$variant->price_amount" :currency="$variant->currency" />
                @endif
            </div>
            @if($variant)
                <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950">Add</button>
                </form>
            @endif
        </div>
    </div>
</article>

