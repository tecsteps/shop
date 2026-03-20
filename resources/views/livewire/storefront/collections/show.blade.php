<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        @include('storefront.components.breadcrumbs', ['items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Collections', 'url' => '/collections'],
            ['label' => $collection->title],
        ]])

        {{-- Header --}}
        <div class="mt-6">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $collection->title }}</h1>
            @if($collection->description_html)
                <div class="prose dark:prose-invert mt-2 max-w-none text-sm">
                    {!! $collection->description_html !!}
                </div>
            @endif
        </div>

        {{-- Toolbar --}}
        <div class="mt-6 flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
            <select wire:model.live="sort" class="rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                <option value="featured">Featured</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="newest">Newest</option>
            </select>
        </div>

        <div class="mt-6 flex gap-8">
            {{-- Filter Sidebar (desktop) --}}
            <aside class="hidden w-56 shrink-0 lg:block">
                <div class="space-y-6">
                    {{-- Product Type Filter --}}
                    @if($productType)
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-3 py-1 text-xs font-medium dark:bg-zinc-700">
                                {{ $productType }}
                                <button wire:click="$set('productType', '')" class="ml-1 hover:text-zinc-900 dark:hover:text-white">&times;</button>
                            </span>
                        </div>
                    @endif

                    @if($productType || $vendor)
                        <button wire:click="clearFilters" class="text-sm text-zinc-600 underline hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            Clear all filters
                        </button>
                    @endif
                </div>
            </aside>

            {{-- Product Grid --}}
            <div class="flex-1" wire:loading.class="opacity-50">
                @if($products->isEmpty())
                    <div class="py-16 text-center">
                        <p class="text-lg font-medium text-zinc-900 dark:text-white">No products found</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Try adjusting your filters or browse our full collection.</p>
                        <button wire:click="clearFilters" class="mt-4 rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                            Clear filters
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                        @foreach($products as $product)
                            @include('storefront.components.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
