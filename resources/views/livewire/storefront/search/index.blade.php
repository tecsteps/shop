<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
            <h1 class="text-3xl font-semibold tracking-normal">Search</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $products->total() }} products found.</p>
        </div>
        <label class="block w-full max-w-xl">
            <span class="sr-only">Search products</span>
            <input wire:model.live.debounce.250ms="q" type="search" placeholder="Search products" class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-base text-zinc-950 outline-none focus:border-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white">
        </label>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">
        <aside class="space-y-6">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-500">Filters</h2>
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-zinc-700 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white">
                        Clear
                    </button>
                </div>

                <label class="grid gap-2 text-sm font-medium">
                    Vendor
                    <select wire:model.live="vendor" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">Any vendor</option>
                        @foreach($facets['vendors'] as $facet)
                            <option wire:key="vendor-{{ $facet['value'] }}" value="{{ $facet['value'] }}">{{ $facet['value'] }} ({{ $facet['count'] }})</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium">
                    Product type
                    <select wire:model.live="productType" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">Any type</option>
                        @foreach($facets['product_types'] as $facet)
                            <option wire:key="type-{{ $facet['value'] }}" value="{{ $facet['value'] }}">{{ $facet['value'] }} ({{ $facet['count'] }})</option>
                        @endforeach
                    </select>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="grid gap-2 text-sm font-medium">
                        Min
                        <input wire:model.live.debounce.300ms="minPrice" type="number" min="0" step="0.01" placeholder="0" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </label>
                    <label class="grid gap-2 text-sm font-medium">
                        Max
                        <input wire:model.live.debounce.300ms="maxPrice" type="number" min="0" step="0.01" placeholder="100" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </label>
                </div>

                <label class="flex items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium dark:border-zinc-800">
                    <input wire:model.live="inStock" type="checkbox" class="rounded border-zinc-300 text-zinc-950 focus:ring-zinc-950 dark:border-zinc-700">
                    In stock
                </label>
            </div>
        </aside>

        <section>
            <div class="mb-5 flex justify-end">
                <label class="flex items-center gap-3 text-sm font-medium">
                    Sort
                    <select wire:model.live="sort" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="relevance">Relevance</option>
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                    </select>
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($products as $product)
                    @include('storefront.components.product-card', ['product' => $product])
                @empty
                    <div class="rounded-lg border border-zinc-200 p-6 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        No products found.
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </section>
    </div>
</div>
