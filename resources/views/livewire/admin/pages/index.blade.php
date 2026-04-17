<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button href="{{ route('admin.pages.create') }}" variant="primary" icon="plus" wire:navigate>New page</flux:button>
    </div>

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($pages->isEmpty())
            <div class="p-10 text-center text-zinc-500">No pages yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Handle</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($pages as $page)
                        <flux:table.row>
                            <flux:table.cell>{{ $page->title }}</flux:table.cell>
                            <flux:table.cell>/{{ $page->handle }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm">{{ $page->status->value }}</flux:badge></flux:table.cell>
                            <flux:table.cell><flux:button size="xs" variant="ghost" href="{{ route('admin.pages.edit', $page) }}" wire:navigate>Edit</flux:button></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
