<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Collections</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate>
            Add collection
        </flux:button>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search collections..."
            class="sm:max-w-sm"
        />

        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
        </flux:select>
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        @if ($this->collections->total() === 0 && $search === '' && $statusFilter === 'all')
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon.rectangle-stack class="size-7" />
                </div>
                <flux:heading size="lg" class="mt-4">Create your first collection</flux:heading>
                <flux:text class="mt-1">Group products together to make them easier to find.</flux:text>
                <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate class="mt-6">
                    Add collection
                </flux:button>
            </div>
        @else
            <flux:card class="overflow-hidden">
                <flux:table :paginate="$this->collections">
                    <flux:table.columns>
                        <flux:table.column>Title</flux:table.column>
                        <flux:table.column>Products</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Updated</flux:table.column>
                        <flux:table.column class="text-end">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->collections as $collection)
                            <flux:table.row :key="$collection->id">
                                <flux:table.cell variant="strong">
                                    <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="hover:underline">
                                        {{ $collection->title }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $collection->products_count }}</flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $colors = ['active' => 'green', 'draft' => 'zinc', 'archived' => 'red'];
                                    @endphp
                                    <flux:badge :color="$colors[$collection->status] ?? 'zinc'" size="sm">
                                        {{ ucfirst($collection->status) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $collection->updated_at?->diffForHumans() }}</flux:table.cell>
                                <flux:table.cell class="text-end">
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $collection->id }})" aria-label="Delete collection" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" align="center" class="py-10 text-zinc-400">
                                    No collections match your filters.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>

    <flux:modal wire:model="confirmingDeleteId" class="max-w-md">
        <flux:heading size="lg">Delete this collection?</flux:heading>
        <flux:text class="mt-2">This will permanently remove the collection. Products are not affected.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('confirmingDeleteId', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteCollection">Delete</flux:button>
        </div>
    </flux:modal>
</div>
