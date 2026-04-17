<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button :href="route('admin.collections.create')" variant="primary" icon="plus" wire:navigate>New collection</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" class="w-72" />

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($collections->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No collections yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Products</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($collections as $collection)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>
                                    {{ $collection->title }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $collection->type->value }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$collection->status->value === 'active' ? 'green' : 'zinc'">
                                    {{ $collection->status->value }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $collection->products_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" :href="route('admin.collections.edit', $collection)" wire:navigate>Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="p-4">{{ $collections->links() }}</div>
        @endif
    </div>
</div>
