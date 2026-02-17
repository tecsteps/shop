<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.pages.create') }}" wire:navigate icon="plus">Add page</flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if ($this->pages->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No pages found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Handle</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->pages as $page)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>{{ $page->title }}</a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $page->handle }}</td>
                            <td class="px-4 py-3">
                                @php $pColor = $page->status->value === 'published' ? 'green' : 'zinc'; @endphp
                                <flux:badge :color="$pColor" size="sm">{{ ucfirst($page->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $page->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $this->pages->links() }}</div>
        @endif
    </div>
</div>
