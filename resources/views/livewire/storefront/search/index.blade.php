<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-8">Search</h1>

        <div class="max-w-xl">
            <flux:input
                wire:model.live.debounce.300ms="query"
                type="search"
                placeholder="Search products..."
                autofocus
            />
        </div>

        <div class="mt-8 text-center py-16">
            <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
            <p class="text-zinc-500 dark:text-zinc-400">
                Search functionality will be available soon.
            </p>
        </div>
    </div>
</div>
