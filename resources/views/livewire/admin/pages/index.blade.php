<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Pages</h2>
        <flux:button variant="primary" href="{{ route('admin.pages.create') }}">Create page</flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($pages as $page)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.pages.edit', $page) }}" class="text-gray-900 hover:underline dark:text-white">{{ $page->title }}</a></td>
                        <td class="px-6 py-4"><flux:badge size="sm" :variant="$page->status->value === 'published' ? 'primary' : 'outline'">{{ ucfirst($page->status->value) }}</flux:badge></td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pages->links() }}</div>
</div>
