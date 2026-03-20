<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl" level="1">Pages</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.pages.create') }}" wire:navigate icon="plus">Add page</flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" />
    </div>

    <flux:table :paginate="$this->pages">
        <flux:table.columns>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Handle</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Updated</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($this->pages as $page)
                <flux:table.row :key="$page->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="hover:underline" wire:navigate>{{ $page->title }}</a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $page->handle }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="($page->status->value ?? $page->status) === 'published' ? 'green' : 'zinc'">{{ ucfirst($page->status->value ?? $page->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $page->updated_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deletePage({{ $page->id }})" wire:confirm="Are you sure?" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">
                        <flux:text class="text-zinc-500">No pages found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
