<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <a href="{{ route('admin.collections.create') }}">
            <flux:button variant="primary">Create Collection</flux:button>
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Products</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($collections as $collection)
                    <tr wire:key="collection-{{ $collection->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.collections.edit', $collection) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $collection->title }}</a>
                        </td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($collection->status->value) }}</flux:badge></td>
                        <td class="px-4 py-3">{{ $collection->products()->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $collections->links() }}</div>
</div>
