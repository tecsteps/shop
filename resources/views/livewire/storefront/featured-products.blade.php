<div>
    <h2 class="mb-6 text-center text-2xl font-bold">Featured Products</h2>
    @if ($products->isEmpty())
        <p class="text-center text-zinc-500 dark:text-zinc-400">No products yet. Check back soon.</p>
    @else
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-storefront.product-card :product="$product" wire:key="featured-{{ $product->getKey() }}" />
            @endforeach
        </div>
    @endif
</div>
