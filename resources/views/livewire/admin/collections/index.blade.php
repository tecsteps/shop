<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Collections</flux:heading>
            <flux:text>Curated product groups for storefront navigation.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate>Add collection</flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections" icon="magnifying-glass" />
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($collections as $collection)
                <div wire:key="admin-collection-{{ $collection->id }}" class="grid gap-4 p-5 sm:grid-cols-[1fr_auto]">
                    <div>
                        <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="font-medium hover:underline">{{ $collection->title }}</a>
                        <div class="text-sm text-zinc-500">{{ $collection->handle }} · {{ $collection->products_count }} products</div>
                    </div>
                    <flux:badge>{{ $collection->status->value }}</flux:badge>
                </div>
            @empty
                <div class="p-10 text-sm text-zinc-500">No collections match the current filters.</div>
            @endforelse
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $collections->links() }}</div>
    </div>
</div>
