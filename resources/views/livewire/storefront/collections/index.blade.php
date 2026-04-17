<div>
    <x-slot:title>Collections</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Collections</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Browse all our collections</p>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Collection grid will be populated when data is wired up --}}
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-gray-500 dark:text-gray-400">No collections found</p>
            </div>
        </div>
    </div>
</div>
