<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate icon="plus">
            Add collection
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search collections..."
                clearable
            />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-auto sm:w-40">
            <flux:select.option value="all">All status</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @if ($this->collections->count() > 0)
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Title</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Products</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Updated</th>
                            <th class="text-right px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->collections as $collection)
                            <tr wire:key="collection-{{ $collection->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-3">
                                    <a
                                        href="{{ route('admin.collections.edit', $collection) }}"
                                        wire:navigate
                                        class="font-medium text-zinc-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                                    >
                                        {{ $collection->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $collection->products_count }}
                                </td>
                                <td class="px-6 py-3">
                                    <flux:badge size="sm" :color="match($collection->status) {
                                        \App\Enums\CollectionStatus::Active => 'green',
                                        \App\Enums\CollectionStatus::Draft => 'zinc',
                                        \App\Enums\CollectionStatus::Archived => 'red',
                                    }">
                                        {{ ucfirst($collection->status->value) }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-3 text-zinc-500 dark:text-zinc-400">
                                    {{ $collection->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <flux:button variant="ghost" size="sm" wire:click="confirmDelete({{ $collection->id }})" icon="trash" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->collections->links() }}
            </div>
        @elseif ($search || $statusFilter !== 'all')
            <div class="p-12 text-center">
                <flux:icon name="magnifying-glass" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">No collections match your filters.</flux:text>
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="rectangle-stack" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Create your first collection</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Group your products into collections.</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate>
                        Add collection
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Delete confirmation modal --}}
    <flux:modal name="confirm-delete-collection" :show="$showDeleteModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete collection?</flux:heading>
            <flux:text>This collection will be permanently deleted. Products in this collection will not be affected.</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" @click="$wire.showDeleteModal = false">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteCollection">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
