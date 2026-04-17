<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Collections') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate>
            {{ __('Add collection') }}
        </flux:button>
    </div>

    <div class="mt-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search collections...') }}" class="w-64" icon="magnifying-glass" />
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Products') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($collections as $collection)
                <flux:table.row :key="$collection->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="hover:underline">
                            {{ $collection->title }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ ucfirst($collection->type->value) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($collection->status->value) {
                            'active' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc',
                        }">{{ ucfirst($collection->status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $collection->products_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="delete({{ $collection->id }})" size="sm" variant="ghost" wire:confirm="{{ __('Delete this collection?') }}">
                            <flux:icon name="trash" variant="mini" />
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <flux:text class="text-center">{{ __('No collections found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $collections->links() }}
    </div>
</div>
