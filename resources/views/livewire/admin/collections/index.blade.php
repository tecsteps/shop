<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl" level="1">Collections</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate icon="plus">Add collection</flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    @if($this->collections->total() > 0)
        <flux:table :paginate="$this->collections">
            <flux:table.columns>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->collections as $collection)
                    <flux:table.row :key="$collection->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.collections.edit', $collection) }}" class="hover:underline" wire:navigate>{{ $collection->title }}</a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $collection->products_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$collection->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($collection->status->value) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $collection->updated_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Are you sure?" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 dark:border-zinc-600">
            <flux:heading size="lg">Create your first collection</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Organize your products into curated groups.</flux:text>
            <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate class="mt-4">Add collection</flux:button>
        </div>
    @endif
</div>
