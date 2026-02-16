<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => url('/collections')],
        ['label' => ucfirst(str_replace('-', ' ', $handle))],
    ]" class="mb-6" />

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ ucfirst(str_replace('-', ' ', $handle)) }}</h1>

    {{-- Toolbar --}}
    <div class="mt-8 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
        <p class="text-sm text-gray-500 dark:text-gray-400">0 products</p>
        <select wire:model.live="sort" class="rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            <option value="featured">Featured</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="newest">Newest</option>
        </select>
    </div>

    {{-- Empty state --}}
    <div class="py-20 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No products found</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Products will appear here once the catalog is seeded.</p>
    </div>
</div>
