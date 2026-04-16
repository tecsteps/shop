<div class="flex flex-col gap-8">
    <flux:heading size="xl">Search</flux:heading>

    <div class="flex gap-2">
        <input type="search" wire:model.live.debounce.300ms="query" class="flex-1 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800" placeholder="Search products..." autofocus data-testid="search-input">
    </div>

    @if (trim($query) === '')
        <div class="text-sm text-zinc-500">Start typing to search our catalog.</div>
    @elseif ($results->isEmpty())
        <div class="rounded-xl bg-zinc-50 p-8 text-center text-zinc-500 dark:bg-zinc-900" data-testid="search-empty">
            No results for &quot;{{ $query }}&quot;.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" data-testid="search-results">
            @foreach ($results as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div>{{ $results->links() }}</div>
    @endif
</div>
