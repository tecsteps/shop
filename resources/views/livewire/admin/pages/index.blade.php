<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Pages') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate>
            {{ __('Add page') }}
        </flux:button>
    </div>

    <div class="mt-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search pages...') }}" class="w-64" icon="magnifying-glass" />
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Updated') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($pages as $page)
                <flux:table.row :key="$page->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="hover:underline">
                            {{ $page->title }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($page->status->value) {
                            'published' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc',
                        }">{{ ucfirst($page->status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $page->updated_at->format('M d, Y') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="delete({{ $page->id }})" size="sm" variant="ghost" wire:confirm="{{ __('Delete this page?') }}">
                            <flux:icon name="trash" variant="mini" />
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <flux:text class="text-center">{{ __('No pages found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $pages->links() }}
    </div>
</div>
