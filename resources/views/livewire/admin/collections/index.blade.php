@php
    $statusColors = ['draft' => 'zinc', 'active' => 'green', 'archived' => 'red'];
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Collections')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Collections') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate data-test="add-collection">
            {{ __('Add collection') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search collections...')" class="max-w-xs" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($this->collections->isEmpty() && $search === '' && $statusFilter === 'all')
        <x-admin.card class="py-16 text-center">
            <flux:icon.rectangle-stack class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">{{ __('Create your first collection') }}</flux:heading>
            <div class="mt-6">
                <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate>{{ __('Add collection') }}</flux:button>
            </div>
        </x-admin.card>
    @else
        <flux:table :paginate="$this->collections">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Products') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Updated') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->collections as $collection)
                    <flux:table.row :key="'col-'.$collection->id">
                        <flux:table.cell variant="strong">
                            <flux:link :href="route('admin.collections.edit', $collection)" wire:navigate>{{ $collection->title }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $collection->products_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$statusColors[$collection->status->value] ?? 'zinc'">{{ ucfirst($collection->status->value) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $collection->updated_at?->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteCollection({{ $collection->id }})" wire:confirm="{{ __('Delete this collection?') }}" :aria-label="__('Delete')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
