<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Search</h1>

        <div class="mt-6">
            <label for="search-input" class="sr-only">Search products</label>
            <input type="search" id="search-input" wire:model.live.debounce.300ms="query"
                   placeholder="Search products..."
                   class="w-full max-w-lg rounded-lg border border-zinc-300 px-4 py-3 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500">
        </div>

        <div class="mt-8 py-12 text-center text-zinc-500 dark:text-zinc-400">
            <p>Search functionality will be available in a future update.</p>
        </div>
    </div>
</div>
