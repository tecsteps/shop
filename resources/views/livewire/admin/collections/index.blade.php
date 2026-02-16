<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Collections</h2>
        <flux:button variant="primary" href="{{ route('admin.collections.create') }}">Create collection</flux:button>
    </div>

    <x-admin.data-table>
        <x-slot:head>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Products</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                </tr>
        </x-slot:head>
        <x-slot:body>
                @forelse($collections as $collection)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.collections.edit', $collection) }}" class="text-gray-900 hover:underline dark:text-white">{{ $collection->title }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $collection->products_count }}</td>
                        <td class="px-6 py-4"><flux:badge size="sm" :variant="$collection->status->value === 'active' ? 'primary' : 'outline'">{{ ucfirst($collection->status->value) }}</flux:badge></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No collections yet.</td></tr>
                @endforelse
        </x-slot:body>
    </x-admin.data-table>
    <div class="mt-4">{{ $collections->links() }}</div>
</div>
