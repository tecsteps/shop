<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.pages.create') }}" wire:navigate>
            <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
            Add page
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Handle</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="page-{{ $page->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-600 dark:text-white">
                                {{ $page->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $page->handle }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$page->status->value === 'published' ? 'green' : 'zinc'" size="sm">
                                {{ ucfirst($page->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $page->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-500">No pages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>
</div>
