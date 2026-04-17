<div>
    <x-slot:title>Collections</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Collections</flux:heading>
        <flux:button href="{{ route('admin.collections.create') }}" variant="primary" wire:navigate>
            Add collection
        </flux:button>
    </div>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections..." icon="magnifying-glass" class="sm:max-w-xs" />
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Products</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($collections as $collection)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                                {{ $collection->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($collection->status->value) { 'active' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc' }">
                                {{ ucfirst($collection->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $collection->products_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No collections found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $collections->links() }}
    </div>
</div>
