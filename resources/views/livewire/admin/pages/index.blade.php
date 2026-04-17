<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button variant="primary" href="{{ url('/admin/pages/create') }}">Add page</flux:button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Handle</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr wire:key="page-{{ $page->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/pages/'.$page->id.'/edit') }}" class="font-medium hover:underline">{{ $page->title }}</a>
                        </td>
                        <td class="px-4 py-2 text-neutral-500">{{ $page->handle }}</td>
                        <td class="px-4 py-2"><flux:badge size="sm">{{ $page->status->value }}</flux:badge></td>
                        <td class="px-4 py-2 text-neutral-500">{{ $page->updated_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $pages->links() }}</div>
</div>
