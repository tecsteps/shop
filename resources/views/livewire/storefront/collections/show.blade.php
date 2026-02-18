<div>
    <x-slot:title>{{ $handle }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold capitalize">{{ str_replace('-', ' ', $handle) }}</h1>

        {{-- Filters and Sort --}}
        <div class="mt-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Showing products</p>
            <div class="flex items-center space-x-4">
                <select class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option>Sort by: Featured</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                    <option>Newest</option>
                </select>
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Products will be populated when data is wired up --}}
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-gray-500 dark:text-gray-400">No products found in this collection</p>
            </div>
        </div>
    </div>
</div>
