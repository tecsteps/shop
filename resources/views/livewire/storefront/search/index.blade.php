<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
            @if($query)
                Search results for "{{ $query }}"
            @else
                Search
            @endif
        </h1>
        <div class="mt-8">
            {{-- Search results with filters will be populated once Phase 8 is complete --}}
            @if($query)
                <p class="text-gray-500 dark:text-gray-400">No results found for "{{ $query }}".</p>
            @endif
        </div>
    </div>
</div>
