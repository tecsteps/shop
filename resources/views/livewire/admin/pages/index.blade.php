<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Pages</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate>
            Add page
        </flux:button>
    </div>

    <div class="mt-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by page title..."
            class="max-w-sm"
        />
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        <flux:card class="overflow-hidden">
            <flux:table :paginate="$this->pages">
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Handle</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Updated</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->pages as $page)
                        <flux:table.row :key="$page->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="hover:underline">
                                    {{ $page->title }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $page->handle }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $colors = ['published' => 'green', 'active' => 'green', 'draft' => 'zinc', 'archived' => 'red'];
                                @endphp
                                <flux:badge :color="$colors[$page->status] ?? 'zinc'" size="sm">
                                    {{ ucfirst($page->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $page->updated_at?->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" align="center" class="py-10 text-zinc-400">
                                No pages found.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
