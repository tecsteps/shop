@props(['product'])

@php
    $variant = $product->variants->sortBy('position')->first();
    $media = $product->media->sortBy('position')->first();
@endphp

<article {{ $attributes->merge(['class' => 'group overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <a href="/products/{{ $product->handle }}" class="block">
        <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
            @if($media)
                <img src="{{ asset('storage/'.$media->storage_key) }}" alt="{{ $media->alt_text ?: $product->title }}" class="h-full w-full object-cover" loading="lazy">
            @else
                <div class="flex h-full w-full items-center justify-center bg-[linear-gradient(135deg,#e4e4e7,#bae6fd,#d9f99d)] px-6 text-center text-lg font-semibold text-zinc-800 dark:bg-[linear-gradient(135deg,#27272a,#0f766e,#1d4ed8)] dark:text-white">
                    {{ $product->title }}
                </div>
            @endif
        </div>
        <div class="grid gap-2 p-4">
            <h3 class="text-sm font-semibold text-zinc-950 group-hover:underline dark:text-white">{{ $product->title }}</h3>
            @if($variant)
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    @include('storefront.components.price', ['amount' => $variant->price_amount, 'currency' => $variant->currency])
                </p>
            @endif
        </div>
    </a>
</article>
