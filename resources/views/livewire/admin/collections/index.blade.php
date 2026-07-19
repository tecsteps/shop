<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Collections</flux:heading>

        @can('create', \App\Models\Collection::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate>Add collection</flux:button>
        @endcan
    </div>

    @if (! $hasCollections)
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="rectangle-stack" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first collection</flux:heading>
            <flux:text class="mt-1">Group products into collections to organize your catalog.</flux:text>
            @can('create', \App\Models\Collection::class)
                <flux:button variant="primary" class="mt-6" :href="route('admin.collections.create')" wire:navigate>Add collection</flux:button>
            @endcan
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search collections..." class="w-full sm:w-72" aria-label="Search collections" />

            <flux:select wire:model.live="statusFilter" class="w-40" aria-label="Status filter">
                <flux:select.option value="all">All statuses</flux:select.option>
                <flux:select.option value="draft">Draft</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="archived">Archived</flux:select.option>
            </flux:select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($collections as $collection)
                        <tr wire:key="collection-{{ $collection->id }}">
                            <td class="px-4 py-3">
                                @can('update', $collection)
                                    <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $collection->title }}
                                    </a>
                                @else
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $collection->title }}</span>
                                @endcan
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$collection->type === \App\Enums\CollectionType::Automated ? 'blue' : 'zinc'">
                                    {{ ucfirst($collection->type->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $collection->products_count }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($collection->status) {
                                    \App\Enums\CollectionStatus::Active => 'green',
                                    \App\Enums\CollectionStatus::Archived => 'red',
                                    default => 'zinc',
                                }">{{ ucfirst($collection->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $collection->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $collection)
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.collections.edit', $collection)" wire:navigate aria-label="Edit {{ $collection->title }}" />
                                    @endcan
                                    @can('delete', $collection)
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $collection->id }})" aria-label="Delete {{ $collection->title }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No collections match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $collections->links() }}
    @endif

    {{-- Delete confirmation modal (spec 03 §19.3) --}}
    <flux:modal wire:model="confirmingDelete" name="confirm-delete-collection" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete this collection?</flux:heading>
            <flux:text>The collection will be permanently removed. Products in it are not deleted.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
