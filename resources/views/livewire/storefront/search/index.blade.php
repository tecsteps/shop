<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('storefront.home')], ['label' => 'Search results']]" class="mb-6" />

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        @if($q)
            Search results for "{{ $q }}"
        @else
            Search
        @endif
    </h1>

    <div class="mt-6">
        <input
            type="search"
            wire:model.live.debounce.300ms="q"
            placeholder="Search products..."
            class="w-full max-w-lg rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            autofocus
        >
    </div>

    @if ($products && $products->isNotEmpty())
        <div class="mt-8 grid grid-cols-2 gap-6 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @elseif ($q && strlen(trim($q)) >= 2)
        <div class="py-20 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No results found</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Try a different search term.</p>
        </div>
    @endif
</div>
