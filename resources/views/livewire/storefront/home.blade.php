<div class="flex flex-col gap-12">
    <section class="rounded-3xl bg-gradient-to-br from-zinc-900 to-zinc-700 p-10 text-white dark:from-zinc-800 dark:to-zinc-900">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Welcome to {{ $currentStore->name }}</h1>
        <p class="mt-3 max-w-xl text-lg text-zinc-300">Browse our curated selection of products and find something you love.</p>
        <a href="{{ route('storefront.collections.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100" wire:navigate>
            Shop now
        </a>
    </section>

    @if ($collections->isNotEmpty())
        <section>
            <div class="mb-6 flex items-end justify-between">
                <h2 class="text-2xl font-semibold">Collections</h2>
                <a href="{{ route('storefront.collections.index') }}" class="text-sm font-medium text-zinc-600 hover:underline dark:text-zinc-400" wire:navigate>View all</a>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="block rounded-2xl bg-zinc-100 p-6 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700" wire:navigate>
                        <div class="text-base font-semibold">{{ $collection->title }}</div>
                        <div class="mt-1 text-xs text-zinc-500">Explore →</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="mb-6 text-2xl font-semibold">Featured products</h2>
        @if ($featured->isEmpty())
            <div class="rounded-xl bg-zinc-50 p-8 text-center text-zinc-500 dark:bg-zinc-900">
                No products yet. Check back soon.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
</div>
