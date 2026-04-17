<div class="flex flex-col gap-8">
    <flux:heading size="xl">Collections</flux:heading>

    @if ($collections->isEmpty())
        <div class="rounded-xl bg-zinc-50 p-10 text-center text-zinc-500 dark:bg-zinc-900">
            No collections yet.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="group rounded-2xl bg-zinc-100 p-8 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700" wire:navigate>
                    <div class="text-xl font-semibold">{{ $collection->title }}</div>
                    <div class="mt-1 text-sm text-zinc-500">{{ $collection->products_count }} products</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
