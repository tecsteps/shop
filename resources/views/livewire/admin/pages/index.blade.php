<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button :href="route('admin.pages.create')" variant="primary" icon="plus" wire:navigate>New page</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" class="w-72" />

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($pages->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No pages yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Handle</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Updated</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($pages as $page)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>
                                    {{ $page->title }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $page->handle }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="match ($page->status->value) { 'published' => 'green', 'draft' => 'zinc', default => 'red' }">
                                    {{ $page->status->value }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $page->updated_at?->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('admin.pages.edit', $page)" wire:navigate>Edit</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="delete({{ $page->id }})" wire:confirm="Delete this page?">Delete</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="p-4">{{ $pages->links() }}</div>
        @endif
    </div>
</div>
