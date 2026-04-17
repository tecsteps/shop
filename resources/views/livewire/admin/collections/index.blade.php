<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button variant="primary" href="{{ url('/admin/collections/create') }}">Add collection</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" class="sm:max-w-xs" />

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Products</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($collections as $collection)
                    <tr wire:key="collection-{{ $collection->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/collections/'.$collection->id.'/edit') }}" class="font-medium hover:underline">{{ $collection->title }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $collection->products_count }}</td>
                        <td class="px-4 py-2">
                            <flux:badge size="sm">{{ $collection->status->value }}</flux:badge>
                        </td>
                        <td class="px-4 py-2 text-neutral-500">{{ $collection->updated_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">No collections yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $collections->links() }}</div>
</div>
