<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.components.breadcrumbs :items="[
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Collections', 'url' => '/collections'],
            ['label' => $collection->title, 'url' => null],
        ]" class="mb-6" />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $collection->title }}</h1>
            @if($collection->description_html)
                <div class="mt-3 max-w-3xl text-gray-600 prose dark:text-gray-400 dark:prose-invert">
                    {!! strip_tags($collection->description_html, '<p><br><strong><em><ul><ol><li><a><h2><h3><h4><blockquote>') !!}
                </div>
            @endif
        </div>

        {{-- Toolbar --}}
        <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            </p>
            <div class="flex items-center gap-2">
                <label for="sort" class="sr-only">Sort by</label>
                <select id="sort"
                        wire:model.live="sort"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <option value="featured">Featured</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="newest">Newest</option>
                    <option value="title-asc">Title: A-Z</option>
                </select>
            </div>
        </div>

        {{-- Product grid --}}
        @if($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6"
                 wire:loading.class="opacity-50">
                @foreach($products as $product)
                    <x-storefront.components.product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No products found</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Try adjusting your filters or browse our full collection.
                </p>
            </div>
        @endif
    </div>
</div>
