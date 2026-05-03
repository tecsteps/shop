<div>
    @php($hero = data_get($themeSettings, 'home.hero', []))

    <section class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
            <div class="flex flex-col justify-center gap-6">
                <div class="space-y-4">
                    <p class="text-sm font-medium uppercase tracking-normal text-blue-700 dark:text-blue-300">{{ data_get($hero, 'eyebrow') }}</p>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-normal text-zinc-950 dark:text-white md:text-5xl">{{ data_get($hero, 'heading', $store->name) }}</h1>
                    <p class="max-w-2xl text-lg text-zinc-600 dark:text-zinc-300">{{ data_get($hero, 'subheading') }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button :href="data_get($hero, 'primary_url', '/collections')" wire:navigate variant="primary">{{ data_get($hero, 'primary_label', 'Shop') }}</flux:button>
                    <flux:button :href="data_get($hero, 'secondary_url', '/collections')" wire:navigate variant="ghost">{{ data_get($hero, 'secondary_label', 'View collections') }}</flux:button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach ($featuredProducts->take(4) as $product)
                    <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950" wire:key="hero-product-{{ $product->getKey() }}">
                        <x-storefront.product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-normal text-zinc-950 dark:text-white">Featured collections</h2>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">Curated paths through the current store catalog.</p>
            </div>
            <a href="{{ route('collections.index') }}" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300" wire:navigate>All collections</a>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($featuredCollections as $collection)
                <a href="{{ route('collections.show', $collection->handle) }}" class="group rounded-lg border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700" wire:navigate wire:key="home-collection-{{ $collection->getKey() }}">
                    <div class="flex aspect-[4/3] items-center justify-center rounded-md bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                        <flux:icon name="rectangle-stack" class="size-10" />
                    </div>
                    <h3 class="mt-4 font-semibold text-zinc-950 group-hover:underline dark:text-white">{{ $collection->title }}</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $collection->products_count }} products</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-normal text-zinc-950 dark:text-white">Featured products</h2>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">Active products currently published for {{ $store->name }}.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($featuredProducts as $product)
                <x-storefront.product-card :product="$product" wire:key="home-product-{{ $product->getKey() }}" />
            @endforeach
        </div>
    </section>
</div>
