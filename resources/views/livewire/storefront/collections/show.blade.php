<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => route('storefront.collections.index')],
        ['label' => $collection->title, 'url' => ''],
    ]" />

    <div class="mb-8">
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">{{ $collection->title }}</h1>
        @if($collection->description_html)
            <div class="mt-3 prose prose-sm dark:prose-invert max-w-2xl">
                {!! $collection->description_html !!}
            </div>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
        </p>
        <div class="flex items-center gap-2">
            <label for="sort" class="sr-only">Sort by</label>
            <select wire:model.live="sort" id="sort"
                    class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="featured">Featured</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="title-asc">Title: A-Z</option>
                <option value="newest">Newest</option>
            </select>
        </div>
    </div>

    {{-- Product Grid --}}
    @if($products->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No products found</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or browse our full collection.</p>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6" wire:loading.class="opacity-50">
            @foreach($products as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
