<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Collections</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Collections</flux:heading>
        @can('create', App\Models\Collection::class)
            <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate>Add collection</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search collections..." aria-label="Search collections" class="max-w-xs flex-1" />
        <flux:select wire:model.live="statusFilter" aria-label="Filter by status" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    @if ($collections->total() === 0 && $this->search === '' && $this->statusFilter === 'all')
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.rectangle-stack class="size-12 text-zinc-400" variant="outline" />
            <flux:heading size="lg" class="mt-4">Create your first collection</flux:heading>
            <flux:text variant="subtle" class="mt-1">Group products into curated collections.</flux:text>
            @can('create', App\Models\Collection::class)
                <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate class="mt-4">Add collection</flux:button>
            @endcan
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($collections as $collection)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="collection-{{ $collection->getKey() }}">
                            <td class="px-4 py-3"><a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium underline" wire:navigate>{{ $collection->title }}</a></td>
                            <td class="px-4 py-3">{{ $collection->products_count }}</td>
                            <td class="px-4 py-3"><flux:badge :color="$collection->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($collection->status->value) }}</flux:badge></td>
                            <td class="px-4 py-3">{{ $collection->updated_at?->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $collection)
                                    <flux:button variant="ghost" size="sm" wire:click="confirmDelete({{ $collection->getKey() }})" aria-label="Delete {{ $collection->title }}">
                                        <flux:icon.trash class="size-4" variant="outline" />
                                    </flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center"><flux:text variant="subtle">No collections match your filters.</flux:text></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $collections->links() }}
        </div>
    @endif

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this collection?</flux:heading>
        <flux:text class="mt-2">The collection will be removed. Products in the collection will not be deleted.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteCollection">Delete</flux:button>
        </div>
    </flux:modal>
</div>
