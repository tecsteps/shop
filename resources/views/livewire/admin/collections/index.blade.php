<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Collections</flux:heading>
            <flux:text class="mt-1">Group products for storefront browsing and merchandising.</flux:text>
        </div>

        <flux:button :href="route('admin.collections.create')" wire:navigate variant="primary" icon="plus">
            Add collection
        </flux:button>
    </div>

    <div class="grid gap-3 md:grid-cols-[1fr_180px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search collections..." aria-label="Search collections" />

        <flux:select wire:model.live="statusFilter" aria-label="Status filter">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Products</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($collections as $collection)
                        @php
                            $statusColor = match ($collection->status->value) {
                                'active' => 'green',
                                'archived' => 'red',
                                default => 'zinc',
                            };
                        @endphp

                        <tr wire:key="admin-collection-{{ $collection->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $collection->title }}
                                </a>
                                <div class="text-xs text-zinc-500">/{{ $collection->handle }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $collection->products_count }}</td>
                            <td class="px-4 py-3"><flux:badge :color="$statusColor">{{ Str::title($collection->status->value) }}</flux:badge></td>
                            <td class="px-4 py-3 text-zinc-500">{{ $collection->updated_at?->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="deleteCollection({{ $collection->getKey() }})" variant="ghost" icon="trash">Delete</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="rectangle-stack" class="size-6" />
                                    </div>
                                    <flux:heading size="lg">No collections found</flux:heading>
                                    <flux:text>Create a collection to organize your products.</flux:text>
                                    <flux:button :href="route('admin.collections.create')" wire:navigate variant="primary">Add collection</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $collections->links() }}
</section>
