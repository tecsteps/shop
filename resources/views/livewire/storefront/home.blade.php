<div class="space-y-20">
    <section class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-50 to-white px-6 py-20 text-center shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:to-zinc-950 sm:px-12 sm:py-28">
        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500">New season</p>
        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-5xl md:text-6xl">
            Thoughtfully made, honestly priced.
        </h1>
        <p class="mx-auto mt-6 max-w-xl text-base text-zinc-600 dark:text-zinc-400">
            A curated collection of timeless goods designed to last. Explore our latest arrivals and find something you will love.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="#featured" class="inline-flex items-center rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Shop the collection
            </a>
            <a href="#recent" class="inline-flex items-center rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-transparent dark:text-zinc-100 dark:hover:bg-zinc-900">
                What is new
            </a>
        </div>
    </section>

    @if ($featuredCollections->isNotEmpty())
        <section id="featured" class="space-y-6">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Featured collections</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Hand-picked edits for every occasion.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($featuredCollections as $collection)
                    <a href="/collections/{{ $collection->handle }}" class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="aspect-[4/3] bg-gradient-to-br from-zinc-200 to-zinc-100 transition duration-500 group-hover:scale-105 dark:from-zinc-800 dark:to-zinc-900"></div>
                        <div class="absolute inset-0 flex flex-col items-start justify-end bg-gradient-to-t from-black/60 via-black/10 to-transparent p-6 text-white">
                            <h3 class="text-xl font-semibold">{{ $collection->title }}</h3>
                            <span class="mt-1 text-sm opacity-80">Shop now</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section id="recent" class="space-y-6">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">New arrivals</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Fresh goods, just in.</p>
            </div>
        </div>

        @if ($recentProducts->isNotEmpty())
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($recentProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">No products yet. Check back soon.</p>
            </div>
        @endif
    </section>
</div>
