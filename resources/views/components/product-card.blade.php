@props(['product'])
@php
    $variant = $product->defaultVariant();
    $image = $product->media->first();
@endphp
<a href="{{ route('storefront.products.show', $product->handle) }}" class="group block overflow-hidden rounded-2xl bg-white ring-1 ring-zinc-200 transition hover:shadow-md dark:bg-zinc-900 dark:ring-zinc-800" wire:navigate>
    <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
        @if ($image)
            <img src="{{ asset('storage/'.$image->storage_key) }}" alt="{{ $image->alt_text ?? $product->title }}" class="h-full w-full object-cover transition group-hover:scale-105" />
        @else
            <div class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-700">
                <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 12l8-8 4 4 4-4 4 4-8 8H2z" />
                </svg>
            </div>
        @endif
    </div>
    <div class="p-4">
        <div class="text-sm font-medium text-zinc-900 group-hover:underline dark:text-white">{{ $product->title }}</div>
        @if ($variant)
            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $variant->currency }} {{ number_format($variant->price_amount / 100, 2) }}
            </div>
        @endif
    </div>
</a>
