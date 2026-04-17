<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate>
            <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
            Add collection
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Products</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($collections as $collection)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="col-{{ $collection->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-600 dark:text-white">
                                {{ $collection->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $collection->products_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $collection->updated_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button variant="ghost" size="sm" wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Are you sure?">
                                <flux:icon name="trash" variant="outline" class="h-4 w-4 text-red-500" />
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center">
                            <flux:heading size="lg">Create your first collection</flux:heading>
                            <flux:button variant="primary" href="{{ route('admin.collections.create') }}" wire:navigate class="mt-3">
                                Add collection
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $collections->links() }}</div>
</div>
