<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Search</h1>

        {{-- Placeholder: search logic in Phase 8 --}}
        <div class="mt-6">
            <input type="text"
                   wire:model="query"
                   placeholder="Search products..."
                   class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
        </div>

        @if($query)
            <p class="mt-6 text-sm text-zinc-600 dark:text-zinc-400">Search results for "{{ $query }}" will be available in a future update.</p>
        @endif
    </div>
</div>
