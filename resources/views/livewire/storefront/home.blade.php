<div>
    <section class="bg-zinc-950 text-white dark:bg-black">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_0.8fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center gap-6">
                <h1 class="max-w-3xl text-4xl font-semibold tracking-normal sm:text-5xl">{{ data_get($settings, 'home.hero_heading') }}</h1>
                <p class="max-w-2xl text-lg leading-8 text-zinc-300">{{ data_get($settings, 'home.hero_subheading') }}</p>
                <div>
                    <a href="{{ data_get($settings, 'home.hero_cta_url') }}" class="inline-flex rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-zinc-200">
                        {{ data_get($settings, 'home.hero_cta_label') }}
                    </a>
                </div>
            </div>
            <div class="min-h-72 rounded-lg bg-[linear-gradient(135deg,#f8fafc,#bae6fd,#bbf7d0)] p-6 text-zinc-950 shadow-2xl dark:bg-[linear-gradient(135deg,#18181b,#0f766e,#1d4ed8)] dark:text-white">
                <div class="flex h-full flex-col justify-end gap-3">
                    <div class="text-sm font-medium uppercase tracking-normal text-zinc-600 dark:text-zinc-300">Featured edit</div>
                    <div class="text-2xl font-semibold">Summer Essentials</div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-normal">Featured Collections</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Curated selections from {{ $store->name }}.</p>
            </div>
            <a href="/collections" class="text-sm font-semibold hover:underline">View all</a>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($collections as $collection)
                <a wire:key="home-collection-{{ $collection->id }}" href="/collections/{{ $collection->handle }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                    <h3 class="font-semibold">{{ $collection->title }}</h3>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ strip_tags($collection->description_html) }}</div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-normal">Featured Products</h2>
            <a href="/search" class="text-sm font-semibold hover:underline">Search products</a>
        </div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($products as $product)
                @include('storefront.components.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
</div>
