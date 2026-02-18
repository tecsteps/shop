<div>
    <x-slot:title>Search</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Search</h1>

        <div class="mt-6">
            <input type="text"
                   wire:model.live.debounce.300ms="query"
                   placeholder="Search products..."
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 text-lg focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
        </div>

        @if ($query)
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Showing results for "{{ $query }}"
            </p>
        @endif

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Search results will be populated when search service exists --}}
            @if ($query)
                <div class="col-span-full rounded-lg border border-gray-200 p-12 text-center dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">No results found for "{{ $query }}"</p>
                </div>
            @endif
        </div>
    </div>
</div>
