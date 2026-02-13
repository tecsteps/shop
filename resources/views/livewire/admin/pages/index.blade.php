<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <a href="{{ route('admin.pages.create') }}">
            <flux:button variant="primary">Create Page</flux:button>
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Handle</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr wire:key="page-{{ $page->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.pages.edit', $page) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $page->title }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $page->handle }}</td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($page->status->value) }}</flux:badge></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $pages->links() }}</div>
</div>
