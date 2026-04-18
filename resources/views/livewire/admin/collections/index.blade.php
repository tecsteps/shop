<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button href="{{ route('admin.collections.create') }}" variant="primary" wire:navigate>New collection</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." />
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Title</th>
                    <th class="p-3">Handle</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($collections as $collection)
                    <tr wire:key="collection-{{ $collection->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><a class="text-sky-600 hover:underline" href="{{ route('admin.collections.edit', $collection) }}" wire:navigate>{{ $collection->title }}</a></td>
                        <td class="p-3">{{ $collection->handle }}</td>
                        <td class="p-3">{{ $collection->type?->value }}</td>
                        <td class="p-3">{{ $collection->status?->value }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="4">No collections yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $collections->links() }}</div>
</div>
