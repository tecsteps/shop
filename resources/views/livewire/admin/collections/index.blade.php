<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
    </div>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" />
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Products</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($collections as $collection)
                    <tr wire:key="collection-{{ $collection->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $collection->title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $collection->products_count }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="$collection->status === 'published' ? 'green' : 'yellow'">{{ ucfirst($collection->status) }}</flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No collections found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $collections->links() }}</div>
</div>
