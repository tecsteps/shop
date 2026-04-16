<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button href="{{ route('admin.collections.create') }}" variant="primary" icon="plus" wire:navigate>New collection</flux:button>
    </div>

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($collections->isEmpty())
            <div class="p-10 text-center text-zinc-500">No collections yet.</div>
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
                            <flux:table.cell><div class="font-medium">{{ $collection->title }}</div></flux:table.cell>
                            <flux:table.cell>{{ $collection->type->value }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$collection->status->value === 'active' ? 'emerald' : 'zinc'">{{ $collection->status->value }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $collection->products_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" variant="ghost" href="{{ route('admin.collections.edit', $collection) }}" wire:navigate>Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
