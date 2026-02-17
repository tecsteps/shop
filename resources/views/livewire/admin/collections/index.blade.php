<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate icon="plus">Add collection</flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if ($this->collections->isEmpty() && ! $search)
            <div class="flex flex-col items-center justify-center py-16">
                <flux:icon name="rectangle-stack" class="mb-4 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg">Create your first collection</flux:heading>
                <flux:text class="mb-4 mt-1">Organize your products into collections.</flux:text>
                <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate>Add collection</flux:button>
            </div>
        @elseif ($this->collections->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No collections match your search.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Products</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->collections as $collection)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>{{ $collection->title }}</a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $collection->products_count }}</td>
                            <td class="px-4 py-3">
                                @php $cColor = $collection->status->value === 'active' ? 'green' : 'zinc'; @endphp
                                <flux:badge :color="$cColor" size="sm">{{ ucfirst($collection->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $collection->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $this->collections->links() }}</div>
        @endif
    </div>
</div>
