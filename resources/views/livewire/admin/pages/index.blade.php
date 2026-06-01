@php
    $statusColors = ['draft' => 'zinc', 'published' => 'green', 'archived' => 'red'];
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Pages')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Pages') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate data-test="add-page">{{ __('Add page') }}</flux:button>
    </div>

    <div class="mb-4 max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search pages...')" />
    </div>

    <flux:table :paginate="$this->pages">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Handle') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Updated') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->pages as $page)
                <flux:table.row :key="'page-'.$page->id">
                    <flux:table.cell variant="strong"><flux:link :href="route('admin.pages.edit', $page)" wire:navigate>{{ $page->title }}</flux:link></flux:table.cell>
                    <flux:table.cell>{{ $page->handle }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$statusColors[$page->status->value] ?? 'zinc'">{{ ucfirst($page->status->value) }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $page->updated_at?->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">{{ __('No pages yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
