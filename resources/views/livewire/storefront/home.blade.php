@php($store = app('current_store'))

<div>
    <section class="relative overflow-hidden bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-24 text-center sm:px-6 sm:py-32 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Welcome to {{ $store->name }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-300">
                Discover our latest collections and find something you'll love.
            </p>
            <div class="mt-8">
                <flux:button :href="route('storefront.collections.index')" wire:navigate variant="primary">
                    Shop now
                </flux:button>
            </div>
        </div>
    </section>

    @if ($collections->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Shop by Collection</h2>
            <div class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}" wire:navigate wire:key="home-collection-{{ $collection->id }}" class="group">
                        <div class="aspect-3/4 overflow-hidden rounded-xl bg-zinc-100 transition-transform group-hover:scale-[1.02] dark:bg-zinc-800"></div>
                        <p class="mt-2 font-medium text-zinc-900 dark:text-white">{{ $collection->title }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($products->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Featured Products</h2>
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-storefront.product-card :product="$product" wire:key="home-product-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-zinc-50 py-16 dark:bg-zinc-900">
        <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Stay in the loop</h2>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and new arrivals.</p>
            <form class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center" onsubmit="return false;">
                <flux:input type="email" placeholder="Your email address" class="sm:w-72" aria-label="Email address" required />
                <flux:button type="submit" variant="primary">Subscribe</flux:button>
            </form>
        </div>
    </section>
</div>
